<?php

namespace Intranet\Modules\UserImport;

use App\Modules\Support\ModuleManifest;
use App\Modules\Support\ModuleServiceProvider;

/**
 * Anmelde-Klasse des Benutzer-Import-Moduls.
 *
 * Routen, Views und Migrationen lädt die Basisklasse automatisch anhand der
 * Ordnerstruktur – hier beschreiben wir nur das Manifest (Menüeintrag).
 */
class UserImportServiceProvider extends ModuleServiceProvider
{
    public function manifest(): ModuleManifest
    {
        return ModuleManifest::make('userimport', 'Benutzer-Import', icon: 'users')
            ->item('index', 'Importe', 'module.userimport.index');
    }
}
