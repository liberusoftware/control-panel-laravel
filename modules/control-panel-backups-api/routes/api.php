<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\BackupsApi\Http\Controllers\SnapshotController;

Route::prefix('api/v1/control-panel/backups')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/snapshots', [SnapshotController::class, 'index'])->name('control-panel.backups.snapshots.index');
    Route::post('/snapshots', [SnapshotController::class, 'store'])->name('control-panel.backups.snapshots.store');
    Route::patch('/policies/{policy}', [SnapshotController::class, 'updatePolicy'])->name('control-panel.backups.policies.update');
    Route::delete('/policies/{policy}', [SnapshotController::class, 'deletePolicy'])->name('control-panel.backups.policies.delete');
    Route::post('/snapshots/{snapshot}/verify', [SnapshotController::class, 'verify'])->name('control-panel.backups.snapshots.verify');
    Route::post('/snapshots/{snapshot}/restore', [SnapshotController::class, 'restore'])->name('control-panel.backups.snapshots.restore');
    Route::post('/destinations', [SnapshotController::class, 'destination'])->name('control-panel.backups.destinations.store');
    Route::patch('/destinations/{destination}', [SnapshotController::class, 'updateDestination'])->name('control-panel.backups.destinations.update');
    Route::delete('/destinations/{destination}', [SnapshotController::class, 'deleteDestination'])->name('control-panel.backups.destinations.delete');
    Route::post('/schedules', [SnapshotController::class, 'schedule'])->name('control-panel.backups.schedules.store');
    Route::patch('/schedules/{schedule}', [SnapshotController::class, 'updateSchedule'])->name('control-panel.backups.schedules.update');
    Route::delete('/schedules/{schedule}', [SnapshotController::class, 'deleteSchedule'])->name('control-panel.backups.schedules.delete');
    Route::post('/features', [SnapshotController::class, 'feature'])->name('control-panel.backups.features.store');
    Route::get('snapshots/{snapshot}', [SnapshotController::class, 'show'])->name('control-panel.backups.show');
});
