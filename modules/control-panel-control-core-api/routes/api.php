<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\ControlCoreApi\Http\Controllers\AuditController;
use Liberu\ControlPanel\ControlCoreApi\Http\Controllers\InventoryController;
use Liberu\ControlPanel\ControlCoreApi\Http\Controllers\NodeController;
use Liberu\ControlPanel\ControlCoreApi\Http\Controllers\OperationLockController;
use Liberu\ControlPanel\ControlCoreApi\Http\Controllers\OperationTaskController;

Route::prefix('api/v1/control-panel/control-core')
    ->middleware(['api', 'auth:sanctum', 'throttle:60,1'])
    ->group(function (): void {
        Route::get('nodes', [NodeController::class, 'index'])->name('control-panel.control-core.nodes.index');
        Route::post('nodes', [NodeController::class, 'store'])->name('control-panel.control-core.nodes.store');
        Route::get('nodes/{node}', [NodeController::class, 'show'])->name('control-panel.control-core.nodes.show');
        Route::patch('nodes/{node}/desired-state', [NodeController::class, 'updateDesiredState'])->name('control-panel.control-core.nodes.desired-state');
        Route::patch('nodes/{node}/status', [NodeController::class, 'updateStatus'])->name('control-panel.control-core.nodes.status');
        Route::post('nodes/{node}/decommission', [NodeController::class, 'decommission'])->name('control-panel.control-core.nodes.decommission');
        Route::put('nodes/{node}/capabilities', [NodeController::class, 'capabilities'])->name('control-panel.control-core.nodes.capabilities');
        Route::post('nodes/{node}/credentials', [NodeController::class, 'credential'])->name('control-panel.control-core.nodes.credentials.store');
        Route::post('credentials/generate-key-pair', [NodeController::class, 'generateKeyPair'])->name('control-panel.control-core.credentials.generate-key-pair');
        Route::post('credentials/{credential}/revoke', [NodeController::class, 'revokeCredential'])->name('control-panel.control-core.credentials.revoke');
        Route::patch('credentials/{credential}', [NodeController::class, 'updateCredential'])->name('control-panel.control-core.credentials.update');
        Route::post('credentials/{credential}/expire', [NodeController::class, 'expireCredential'])->name('control-panel.control-core.credentials.expire');
        Route::get('tasks', [OperationTaskController::class, 'index'])->name('control-panel.control-core.tasks.index');
        Route::post('tasks', [OperationTaskController::class, 'store'])->name('control-panel.control-core.tasks.store');
        Route::post('tasks/{task}/transition', [OperationTaskController::class, 'transition'])->name('control-panel.control-core.tasks.transition');
        Route::get('inventory', [InventoryController::class, 'index'])->name('control-panel.control-core.inventory.index');
        Route::post('inventory', [InventoryController::class, 'store'])->name('control-panel.control-core.inventory.store');
        Route::get('audit', [AuditController::class, 'index'])->name('control-panel.control-core.audit.index');
        Route::get('locks', [OperationLockController::class, 'index'])->name('control-panel.control-core.locks.index');
        Route::post('locks', [OperationLockController::class, 'store'])->name('control-panel.control-core.locks.store');
        Route::delete('locks/{lock}', [OperationLockController::class, 'destroy'])->name('control-panel.control-core.locks.destroy');
    });
