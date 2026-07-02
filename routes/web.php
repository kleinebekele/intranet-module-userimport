<?php

use Illuminate\Support\Facades\Route;
use Intranet\Modules\UserImport\Http\Controllers\UserImportController;

/*
 | Routen des Benutzer-Import-Moduls.
 |
 | Konvention:
 |  - URL-Präfix:  modules/userimport
 |  - Namen:       module.userimport.*
 |  - Middleware:  'web' + 'auth' + 'admin'  (nur eingeloggte Administratoren)
*/
Route::middleware(['web', 'auth', 'admin'])
    ->prefix('modules/userimport')
    ->name('module.userimport.')
    ->group(function () {
        Route::get('/', [UserImportController::class, 'index'])->name('index');
        Route::post('/', [UserImportController::class, 'store'])->name('store');
    });
