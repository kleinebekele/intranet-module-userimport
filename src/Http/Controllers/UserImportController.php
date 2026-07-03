<?php

namespace Intranet\Modules\UserImport\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Intranet\Modules\UserImport\Models\UserImport;
use Intranet\Modules\UserImport\Notifications\AccountInvitation;

/**
 * Import von Benutzern aus einer hochgeladenen CSV-Datei.
 *
 * Erwartete Spalten: externe_id, email, name, role1 … role10, parent1 … parent4
 *
 * Regeln:
 *  - Reiner Import: bestehende Benutzer werden NICHT verändert.
 *  - Existiert die E-Mail bereits, wird die ganze Zeile blockiert (übersprungen).
 *  - Unbekannte Rollen werden automatisch in der roles-Tabelle angelegt.
 *  - Jeder neu angelegte Benutzer erhält eine Einladungs-Mail zum Passwort-Setzen.
 *  - parent1 … parent4 verweisen (per externe_id ODER E-Mail) auf die Eltern des
 *    Benutzers und werden nach dem Anlegen aller Zeilen in users_parents
 *    hinterlegt (2. Durchgang → Reihenfolge in der Datei ist egal).
 */
class UserImportController
{
    public function index(): View
    {
        $imports = UserImport::with('user')->latest()->take(25)->get();

        return view('userimport::index', compact('imports'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'], // max. 5 MB
        ]);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $file->store('imports'); // Kopie zur Nachvollziehbarkeit in storage/app/imports/

        $total = $created = $skipped = $linkedParents = 0;
        $status = 'completed';
        $error = null;

        try {
            $rows = $this->readCsv($file->getRealPath());

            // 1. Durchgang: Benutzer anlegen.
            foreach ($rows as $row) {
                $total++;

                $email = trim($row['email'] ?? '');
                $name = trim($row['name'] ?? '');

                // Ungültige Zeile (fehlende/ungültige E-Mail) -> überspringen.
                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    continue;
                }

                // Reiner Import: existiert die E-Mail bereits, ganze Zeile blockieren.
                if (User::where('email', $email)->exists()) {
                    $skipped++;
                    continue;
                }

                $user = new User();
                $user->forceFill([
                    'name' => $name !== '' ? $name : $email,
                    'email' => $email,
                    'externe_id' => trim($row['externe_id'] ?? '') ?: null,
                    'source' => 'import',
                    'password' => null,
                    'email_verified_at' => now(), // stammt aus vertrauenswürdiger Quelle
                ])->save();

                $this->syncRoles($user, $row);

                // Einladung mit Link zum Passwort-Setzen verschicken.
                $token = Password::broker()->createToken($user);
                $user->notify(new AccountInvitation($token));

                $created++;
            }

            // 2. Durchgang: Eltern-Kind-Beziehungen verknüpfen (alle Benutzer existieren nun).
            $linkedParents = $this->linkParents($rows);
        } catch (\Throwable $e) {
            $status = 'failed';
            $error = $e->getMessage();
        }

        UserImport::create([
            'user_id' => $request->user()->id,
            'filename' => $filename,
            'status' => $status,
            'total_rows' => $total,
            'created_count' => $created,
            'skipped_count' => $skipped,
            'error_message' => $error,
        ]);

        $message = $status === 'completed'
            ? "Import abgeschlossen: {$created} neu angelegt, {$skipped} übersprungen (von {$total} Zeilen), {$linkedParents} Eltern-Verknüpfungen."
            : "Import fehlgeschlagen: {$error}";

        return redirect()->route('module.userimport.index')->with('status', $message);
    }

    /**
     * Rollen aus den Spalten role1 … role10 lesen, leere ignorieren,
     * unbekannte automatisch anlegen und mit dem Benutzer verknüpfen.
     */
    private function syncRoles(User $user, array $row): void
    {
        $roleIds = [];

        foreach ($row as $key => $value) {
            if (! str_starts_with($key, 'role')) {
                continue;
            }
            $roleId = trim((string) $value);
            if ($roleId === '') {
                continue;
            }
            Role::firstOrCreate(['role_id' => $roleId], ['name' => $roleId]);
            $roleIds[$roleId] = $roleId; // als Set -> keine Dubletten
        }

        if ($roleIds) {
            $user->roles()->syncWithoutDetaching(array_keys($roleIds));
        }
    }

    /**
     * 2. Durchgang: verknüpft parent1 … parent4 mit dem Benutzer der Zeile in
     * users_parents. Eltern werden per externe_id ODER E-Mail (enthält „@")
     * aufgelöst; nur bereits existierende Benutzer werden verlinkt. Idempotent.
     *
     * @param  array<int, array<string, string>>  $rows
     */
    private function linkParents(array $rows): int
    {
        $linked = 0;

        foreach ($rows as $row) {
            $childKey = trim($row['externe_id'] ?? '');
            if ($childKey === '') {
                continue;
            }

            $child = User::where('externe_id', $childKey)->first();
            if (! $child) {
                continue;
            }

            $parentIds = [];
            foreach (['parent1', 'parent2', 'parent3', 'parent4'] as $col) {
                $ref = trim($row[$col] ?? '');
                if ($ref === '') {
                    continue;
                }
                $parent = str_contains($ref, '@')
                    ? User::where('email', $ref)->first()
                    : User::where('externe_id', $ref)->first();
                if ($parent && $parent->id !== $child->id) {
                    $parentIds[$parent->id] = $parent->id; // Set → keine Dubletten
                }
            }

            if ($parentIds !== []) {
                $child->parents()->syncWithoutDetaching(array_values($parentIds));
                $linked += count($parentIds);
            }
        }

        return $linked;
    }

    /**
     * Liest eine CSV robust ein: erkennt das Trennzeichen (',' oder ';'),
     * entfernt ein evtl. UTF-8-BOM und liefert je Zeile ein assoziatives
     * Array (kleingeschriebener Spaltenname => Wert).
     *
     * @return array<int, array<string, string>>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Die Datei konnte nicht geöffnet werden.');
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);

            return [];
        }

        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine); // BOM entfernen
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        $header = array_map(
            static fn ($h) => strtolower(trim((string) $h)),
            str_getcsv($firstLine, $delimiter, '"', '')
        );
        $columns = count($header);

        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            // Komplett leere Zeilen überspringen.
            if (count($data) === 1 && trim((string) $data[0]) === '') {
                continue;
            }
            // Auf Header-Breite bringen (fehlende Spalten -> '', zu viele -> abschneiden).
            $data = array_pad(array_slice($data, 0, $columns), $columns, '');
            $rows[] = array_combine($header, $data);
        }

        fclose($handle);

        return $rows;
    }
}
