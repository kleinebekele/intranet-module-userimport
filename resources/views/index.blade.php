<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-gray-800">Benutzer-Import</h1>
    </x-slot>

    <div class="max-w-3xl space-y-8">

        {{-- Upload-Formular --}}
        <section class="rounded-xl border border-gray-200 bg-white p-6">
            <h2 class="text-lg font-medium text-gray-800">CSV-Datei importieren</h2>
            <p class="mt-1 text-sm text-gray-500">
                Erwartete Spalten:
                <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs">externe_id, email, name, role1 … role10</code>
            </p>

            <form method="POST" action="{{ route('module.userimport.store') }}"
                  enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf

                <div>
                    <input type="file" name="file" accept=".csv,text/csv,text/plain"
                           class="block w-full text-sm text-gray-600
                                  file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2
                                  file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                    @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Importieren
                    </button>
                    <span class="text-xs text-gray-400">Bestehende E-Mail-Adressen werden übersprungen, nichts wird überschrieben.</span>
                </div>
            </form>
        </section>

        {{-- Letzte Importe --}}
        <section>
            <h2 class="text-lg font-medium text-gray-800 mb-3">Letzte Importe</h2>

            @if ($imports->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500">
                    Noch keine Importe. Lade oben deine erste CSV-Datei hoch.
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Zeitpunkt</th>
                                <th class="px-4 py-3">Datei</th>
                                <th class="px-4 py-3">Von</th>
                                <th class="px-4 py-3 text-right">Ergebnis</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($imports as $import)
                                <tr>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        {{ $import->created_at->format('d.m.Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $import->filename }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $import->user?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600 whitespace-nowrap">
                                        <span class="text-green-700">{{ $import->created_count }} neu</span>,
                                        <span class="text-gray-500">{{ $import->skipped_count }} übersprungen</span>
                                        <span class="text-gray-400">/ {{ $import->total_rows }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($import->status === 'completed')
                                            <span class="inline-flex rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">erfolgreich</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700"
                                                  title="{{ $import->error_message }}">fehlgeschlagen</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
