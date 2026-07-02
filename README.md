# Benutzer-Import-Modul

Importiert Benutzer aus einer CSV-Datei in die modulare
[Intranet-Plattform](https://github.com/kleinebekele/intranet-core) – inklusive
Rollen-Zuweisung und Einladungs-Mail zum Passwort-Setzen.

## Funktionen

- **Adminpanel** unter *Benutzer-Import* mit Upload-Formular und einer Übersicht
  der letzten Importe (Zeitpunkt, Datei, Ergebnis, Status).
- **Reiner Import:** bestehende Benutzer werden nie verändert. Ist eine E-Mail
  bereits vorhanden, wird die ganze Zeile übersprungen.
- **Rollen:** unbekannte Rollen aus der CSV werden automatisch im Core angelegt
  (`roles`) und n:n mit dem Benutzer verknüpft (`user_roles`).
- **Einladung:** jeder neu angelegte Benutzer erhält eine Mail mit einem Link,
  über den er sein eigenes Passwort festlegt (nutzt Laravels Passwort-Reset).

## Erwartetes CSV-Format

Kopfzeile mit diesen Spalten (Trennzeichen `,` oder `;` wird automatisch erkannt):

```
externe_id,email,name,role1,role2,role3,role4,role5,role6,role7,role8,role9,role10
1001,anna@firma.de,Anna Muster,staff,teacher,,,,,,,,
1002,ben@firma.de,Ben Beispiel,student,,,,,,,,,
```

- `email` ist Pflicht und muss gültig sein – sonst wird die Zeile übersprungen.
- Leere Rollen-Spalten werden ignoriert; ein Benutzer kann bis zu 10 Rollen erhalten.

## Installation

Siehe [MODULES.md](https://github.com/kleinebekele/intranet-core/blob/main/MODULES.md)
des Core. Kurz:

```bash
composer require do1emu/module-userimport:*
php artisan modules:sync
php artisan migrate
```

Der Menüpunkt ist nur für Administratoren erreichbar.
