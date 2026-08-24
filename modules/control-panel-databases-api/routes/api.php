<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\DatabasesApi\Http\Controllers\DatabaseController;

Route::prefix('api/v1/control-panel/databases')->middleware(['api', 'auth:sanctum'])->group(function (): void {
    Route::get('/', [DatabaseController::class, 'index'])->name('control-panel.databases.index');
    Route::post('/', [DatabaseController::class, 'store'])->name('control-panel.databases.store');
});
