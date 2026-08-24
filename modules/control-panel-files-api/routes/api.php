<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\FilesApi\Http\Controllers\FileController;

Route::prefix('api/v1/control-panel/files')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [FileController::class, 'index'])->name('control-panel.files.index');
    Route::post('/', [FileController::class, 'store'])->name('control-panel.files.store');
});
