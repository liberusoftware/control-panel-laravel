<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\BackupsApi\Http\Controllers\SnapshotController;

Route::prefix('api/v1/control-panel/backups')->middleware(['api', 'auth:sanctum'])->group(function (): void {
    Route::get('/snapshots', [SnapshotController::class, 'index'])->name('control-panel.backups.snapshots.index');
    Route::post('/snapshots', [SnapshotController::class, 'store'])->name('control-panel.backups.snapshots.store');
});
