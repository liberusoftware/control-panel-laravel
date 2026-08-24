<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\DatabasesApi\Http\Controllers\DatabaseController;

Route::prefix('api/v1/control-panel/databases')->middleware(['api', 'auth:sanctum'])->group(function (): void {
    Route::get('/', [DatabaseController::class, 'index'])->name('control-panel.databases.index');
    Route::post('/', [DatabaseController::class, 'store'])->name('control-panel.databases.store');
    Route::post('{database}/users', [DatabaseController::class, 'user'])->name('control-panel.databases.users.store');
    Route::post('users/{user}/privileges', [DatabaseController::class, 'privilege'])->name('control-panel.databases.privileges.store');
});
