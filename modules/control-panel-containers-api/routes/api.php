<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\ContainersApi\Http\Controllers\WorkloadController;

Route::prefix('api/v1/control-panel/containers')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [WorkloadController::class, 'index'])->name('control-panel.containers.index');
    Route::post('/', [WorkloadController::class, 'store'])->name('control-panel.containers.store');
    Route::get('/assets', [WorkloadController::class, 'assets'])->name('control-panel.containers.assets.index');
    Route::post('/resources', [WorkloadController::class, 'resourceRecord'])->name('control-panel.containers.resources.store');
    Route::post('/assets', [WorkloadController::class, 'asset'])->name('control-panel.containers.assets.store');
    Route::post('{workload}/start', [WorkloadController::class, 'start'])->name('control-panel.containers.start');
    Route::post('{workload}/stop', [WorkloadController::class, 'stop'])->name('control-panel.containers.stop');
    Route::delete('{workload}', [WorkloadController::class, 'delete'])->name('control-panel.containers.delete');
    Route::get('{workload}', [WorkloadController::class, 'show'])->name('control-panel.containers.show');
});
