<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\FilesApi\Http\Controllers\FileController;

Route::prefix('api/v1/control-panel/files')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [FileController::class, 'index'])->name('control-panel.files.index');
    Route::post('/', [FileController::class, 'store'])->name('control-panel.files.store');
    Route::post('/operations', [FileController::class, 'operation'])->name('control-panel.files.operations.store');
    Route::post('/home-directories', [FileController::class, 'home'])->name('control-panel.files.home-directories.store');
    Route::post('/permissions', [FileController::class, 'permission'])->name('control-panel.files.permissions.store');
    Route::post('/sftp-accounts', [FileController::class, 'sftp'])->name('control-panel.files.sftp-accounts.store');
    Route::post('/retention', [FileController::class, 'retention'])->name('control-panel.files.retention.store');
    Route::get('{file}', [FileController::class, 'show'])->name('control-panel.files.show');
});
