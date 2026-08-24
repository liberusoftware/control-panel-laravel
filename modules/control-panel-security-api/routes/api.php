<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\SecurityApi\Http\Controllers\SecurityFindingController;

Route::prefix('api/v1/control-panel/security')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [SecurityFindingController::class, 'index'])->name('control-panel.security.index');
    Route::post('/', [SecurityFindingController::class, 'store'])->name('control-panel.security.store');
});
