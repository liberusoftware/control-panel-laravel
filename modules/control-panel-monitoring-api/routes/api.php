<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\MonitoringApi\Http\Controllers\MonitorController;

Route::prefix('api/v1/control-panel/monitoring')->middleware(['api', 'auth:sanctum'])->group(function (): void {
    Route::get('/', [MonitorController::class, 'index'])->name('control-panel.monitoring.index');
    Route::post('/', [MonitorController::class, 'store'])->name('control-panel.monitoring.store');
});
