<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\AccountsApi\Http\Controllers\AccountController;

Route::prefix('api/v1/control-panel/accounts')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function (): void {
        Route::get('/', [AccountController::class, 'index'])->name('control-panel.accounts.index');
        Route::post('/', [AccountController::class, 'store'])->name('control-panel.accounts.store');
    });
