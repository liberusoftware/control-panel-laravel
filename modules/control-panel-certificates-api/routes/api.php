<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\CertificatesApi\Http\Controllers\CertificateController;

Route::prefix('api/v1/control-panel/certificates')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [CertificateController::class, 'index'])->name('control-panel.certificates.index');
    Route::post('/', [CertificateController::class, 'store'])->name('control-panel.certificates.store');
    Route::post('/acme-accounts', [CertificateController::class, 'acme'])->name('control-panel.certificates.acme');
    Route::post('/operations', [CertificateController::class, 'operation'])->name('control-panel.certificates.operations');
});
