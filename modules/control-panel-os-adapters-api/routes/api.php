<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\OsAdaptersApi\Http\Controllers\OsAdapterController;

Route::prefix('api/v1/control-panel/os-adapters')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [OsAdapterController::class, 'index'])->name('control-panel.os-adapters.index');
    Route::post('/', [OsAdapterController::class, 'store'])->name('control-panel.os-adapters.store');
    Route::post('packages', [OsAdapterController::class, 'package'])->name('control-panel.os-adapters.packages.store');
    Route::post('services', [OsAdapterController::class, 'service'])->name('control-panel.os-adapters.services.store');
    Route::post('firewall-rules', [OsAdapterController::class, 'firewall'])->name('control-panel.os-adapters.firewall.store');
    Route::post('users', [OsAdapterController::class, 'user'])->name('control-panel.os-adapters.users.store');
    Route::post('filesystems', [OsAdapterController::class, 'filesystem'])->name('control-panel.os-adapters.filesystems.store');
    Route::post('repositories', [OsAdapterController::class, 'repository'])->name('control-panel.os-adapters.repositories.store');
    Route::post('support-matrix', [OsAdapterController::class, 'supportMatrix'])->name('control-panel.os-adapters.support-matrix.store');
});
