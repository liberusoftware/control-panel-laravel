<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\MonitoringApi\Http\Controllers\MonitorController;

Route::prefix('api/v1/control-panel/monitoring')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [MonitorController::class, 'index'])->name('control-panel.monitoring.index');
    Route::post('/', [MonitorController::class, 'store'])->name('control-panel.monitoring.store');
    Route::post('/events', [MonitorController::class, 'event'])->name('control-panel.monitoring.events.store');
    Route::post('/events/{event}/resolve', [MonitorController::class, 'resolveEvent'])->name('control-panel.monitoring.events.resolve');
    Route::post('/resources', [MonitorController::class, 'record'])->name('control-panel.monitoring.resources.store');
    Route::post('/maintenance/{window}/cancel', [MonitorController::class, 'cancelMaintenance'])->name('control-panel.monitoring.maintenance.cancel');
    Route::delete('/maintenance/{window}', [MonitorController::class, 'deleteMaintenance'])->name('control-panel.monitoring.maintenance.delete');
    Route::get('{monitor}', [MonitorController::class, 'show'])->name('control-panel.monitoring.show');
});
