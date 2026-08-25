<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\MailApi\Http\Controllers\MailAccountController;

Route::prefix('api/v1/control-panel/mail')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [MailAccountController::class, 'index'])->name('control-panel.mail.index');
    Route::post('/', [MailAccountController::class, 'store'])->name('control-panel.mail.store');
    Route::post('/operations', [MailAccountController::class, 'operation'])->name('control-panel.mail.operations.store');
    Route::post('/aliases', [MailAccountController::class, 'alias'])->name('control-panel.mail.aliases.store');
    Route::post('/controls', [MailAccountController::class, 'controls'])->name('control-panel.mail.controls.store');
    Route::post('/delivery-diagnostics', [MailAccountController::class, 'diagnostic'])->name('control-panel.mail.delivery-diagnostics.store');
});
