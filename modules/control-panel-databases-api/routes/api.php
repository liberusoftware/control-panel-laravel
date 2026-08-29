<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\DatabasesApi\Http\Controllers\DatabaseController;

Route::prefix('api/v1/control-panel/databases')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [DatabaseController::class, 'index'])->name('control-panel.databases.index');
    Route::post('/', [DatabaseController::class, 'store'])->name('control-panel.databases.store');
    Route::patch('{database}', [DatabaseController::class, 'update'])->name('control-panel.databases.update');
    Route::post('{database}/users', [DatabaseController::class, 'user'])->name('control-panel.databases.users.store');
    Route::post('users/{user}/privileges', [DatabaseController::class, 'privilege'])->name('control-panel.databases.privileges.store');
    Route::post('{database}/backups', [DatabaseController::class, 'backup'])->name('control-panel.databases.backups.store');
    Route::post('{database}/health-checks', [DatabaseController::class, 'health'])->name('control-panel.databases.health.store');
    Route::post('{database}/upgrades', [DatabaseController::class, 'upgrade'])->name('control-panel.databases.upgrades.store');
    Route::post('{database}/remote-access', [DatabaseController::class, 'remoteAccess'])->name('control-panel.databases.remote-access.store');
    Route::post('{database}/suspend', [DatabaseController::class, 'suspend'])->name('control-panel.databases.suspend');
    Route::post('{database}/archive', [DatabaseController::class, 'archive'])->name('control-panel.databases.archive');
    Route::delete('{database}', [DatabaseController::class, 'delete'])->name('control-panel.databases.delete');
    Route::get('{database}', [DatabaseController::class, 'show'])->name('control-panel.databases.show');
});
