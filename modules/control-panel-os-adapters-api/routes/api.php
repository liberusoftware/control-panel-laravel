<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\OsAdaptersApi\Http\Controllers\OsAdapterController;

Route::prefix('api/v1/control-panel/os-adapters')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [OsAdapterController::class, 'index'])->name('control-panel.os-adapters.index');
    Route::post('/', [OsAdapterController::class, 'store'])->name('control-panel.os-adapters.store');
    Route::get('services/status', [OsAdapterController::class, 'serviceStatuses'])->name('control-panel.os-adapters.services.status');
    Route::get('services/missing', [OsAdapterController::class, 'missingServices'])->name('control-panel.os-adapters.services.missing');
    Route::get('services/stopped', [OsAdapterController::class, 'stoppedServices'])->name('control-panel.os-adapters.services.stopped');
    Route::get('services/install-commands', [OsAdapterController::class, 'installationCommands'])->name('control-panel.os-adapters.services.install-commands');
    Route::get('services/{service}/check', [OsAdapterController::class, 'checkService'])->name('control-panel.os-adapters.services.check');
    Route::post('packages', [OsAdapterController::class, 'package'])->name('control-panel.os-adapters.packages.store');
    Route::post('services', [OsAdapterController::class, 'service'])->name('control-panel.os-adapters.services.store');
    Route::patch('services/{service}', [OsAdapterController::class, 'updateService'])->name('control-panel.os-adapters.services.update');
    Route::post('firewall-rules', [OsAdapterController::class, 'firewall'])->name('control-panel.os-adapters.firewall.store');
    Route::patch('firewall-rules/{rule}', [OsAdapterController::class, 'updateFirewall'])->name('control-panel.os-adapters.firewall.update');
    Route::delete('firewall-rules/{rule}', [OsAdapterController::class, 'deleteFirewall'])->name('control-panel.os-adapters.firewall.delete');
    Route::post('users', [OsAdapterController::class, 'user'])->name('control-panel.os-adapters.users.store');
    Route::post('filesystems', [OsAdapterController::class, 'filesystem'])->name('control-panel.os-adapters.filesystems.store');
    Route::post('repositories', [OsAdapterController::class, 'repository'])->name('control-panel.os-adapters.repositories.store');
    Route::post('support-matrix', [OsAdapterController::class, 'supportMatrix'])->name('control-panel.os-adapters.support-matrix.store');
    Route::get('{adapter}', [OsAdapterController::class, 'show'])->name('control-panel.os-adapters.show');
});
