<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\ControlCoreApi\Http\Controllers\InventoryController;
use Liberu\ControlPanel\ControlCoreApi\Http\Controllers\NodeController;
use Liberu\ControlPanel\ControlCoreApi\Http\Controllers\OperationTaskController;

Route::prefix('api/v1/control-panel/control-core')
    ->middleware(['api', 'auth:sanctum', 'throttle:60,1'])
    ->group(function (): void {
        Route::get('nodes', [NodeController::class, 'index'])->name('control-panel.control-core.nodes.index');
        Route::post('nodes', [NodeController::class, 'store'])->name('control-panel.control-core.nodes.store');
        Route::get('tasks', [OperationTaskController::class, 'index'])->name('control-panel.control-core.tasks.index');
        Route::post('tasks', [OperationTaskController::class, 'store'])->name('control-panel.control-core.tasks.store');
        Route::get('inventory', [InventoryController::class, 'index'])->name('control-panel.control-core.inventory.index');
        Route::post('inventory', [InventoryController::class, 'store'])->name('control-panel.control-core.inventory.store');
    });
