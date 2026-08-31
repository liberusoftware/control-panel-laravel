<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\MailApi\Http\Controllers\MailAccountController;

Route::prefix('api/v1/control-panel/mail')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [MailAccountController::class, 'index'])->name('control-panel.mail.index');
    Route::post('/', [MailAccountController::class, 'store'])->name('control-panel.mail.store');
    Route::post('/operations', [MailAccountController::class, 'operation'])->name('control-panel.mail.operations.store');
    Route::post('/aliases', [MailAccountController::class, 'alias'])->name('control-panel.mail.aliases.store');
    Route::patch('/aliases/{alias}', [MailAccountController::class, 'updateAlias'])->name('control-panel.mail.aliases.update');
    Route::delete('/aliases/{alias}', [MailAccountController::class, 'deleteAlias'])->name('control-panel.mail.aliases.delete');
    Route::post('/routes', [MailAccountController::class, 'route'])->name('control-panel.mail.routes.store');
    Route::patch('/routes/{route}', [MailAccountController::class, 'updateRoute'])->name('control-panel.mail.routes.update');
    Route::delete('/routes/{route}', [MailAccountController::class, 'deleteRoute'])->name('control-panel.mail.routes.delete');
    Route::post('/controls', [MailAccountController::class, 'controls'])->name('control-panel.mail.controls.store');
    Route::post('/delivery-diagnostics', [MailAccountController::class, 'diagnostic'])->name('control-panel.mail.delivery-diagnostics.store');
    Route::post('/dkim/rotate', [MailAccountController::class, 'rotateDkim'])->name('control-panel.mail.dkim.rotate');
    Route::post('/authentication/configure', [MailAccountController::class, 'authentication'])->name('control-panel.mail.authentication.configure');
    Route::post('/domains', [MailAccountController::class, 'domain'])->name('control-panel.mail.domains.store');
    Route::patch('/{mailAccount}', [MailAccountController::class, 'update'])->name('control-panel.mail.update');
    Route::delete('/{mailAccount}', [MailAccountController::class, 'delete'])->name('control-panel.mail.delete');
    Route::get('/{mailAccount}', [MailAccountController::class, 'show'])->name('control-panel.mail.show');
});
