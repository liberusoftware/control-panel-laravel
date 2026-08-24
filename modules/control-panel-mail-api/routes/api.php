<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\MailApi\Http\Controllers\MailAccountController;

Route::prefix('api/v1/control-panel/mail')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [MailAccountController::class, 'index'])->name('control-panel.mail.index');
    Route::post('/', [MailAccountController::class, 'store'])->name('control-panel.mail.store');
});
