<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\OsAdaptersApi\Http\Controllers\OsAdapterController;

Route::prefix('api/v1/control-panel/os-adapters')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [OsAdapterController::class, 'index'])->name('control-panel.os-adapters.index');
    Route::post('/', [OsAdapterController::class, 'store'])->name('control-panel.os-adapters.store');
});
