<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\AccountsApi\Http\Controllers\AccountController;

Route::prefix('api/v1/control-panel/accounts')
    ->middleware(['api', 'auth:sanctum', 'throttle:60,1'])
    ->group(function (): void {
        Route::get('/', [AccountController::class, 'index'])->name('control-panel.accounts.index');
        Route::post('/', [AccountController::class, 'store'])->name('control-panel.accounts.store');
        Route::post('{account}/suspend', [AccountController::class, 'suspend'])->name('control-panel.accounts.suspend');
        Route::post('{account}/activate', [AccountController::class, 'activate'])->name('control-panel.accounts.activate');
        Route::post('{account}/delegations', [AccountController::class, 'delegate'])->name('control-panel.accounts.delegate');
        Route::patch('{account}/branding', [AccountController::class, 'branding'])->name('control-panel.accounts.branding');
        Route::post('{account}/quota-check', [AccountController::class, 'quotaCheck'])->name('control-panel.accounts.quota-check');
        Route::post('packages', [AccountController::class, 'package'])->name('control-panel.accounts.packages.store');
        Route::get('packages', [AccountController::class, 'packages'])->name('control-panel.accounts.packages.index');
        Route::patch('packages/{package}', [AccountController::class, 'updatePackage'])->name('control-panel.accounts.packages.update');
        Route::get('{account}/delegations', [AccountController::class, 'delegations'])->name('control-panel.accounts.delegations.index');
        Route::post('delegations/{delegation}/revoke', [AccountController::class, 'revokeDelegation'])->name('control-panel.accounts.delegations.revoke');
    });
