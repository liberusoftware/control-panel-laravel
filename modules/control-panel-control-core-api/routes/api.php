<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\ControlCoreApi\Http\Controllers\NodeController;

Route::prefix('api/v1/control-panel/control-core')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function (): void {
        Route::get('nodes', [NodeController::class, 'index'])->name('control-panel.control-core.nodes.index');
        Route::post('nodes', [NodeController::class, 'store'])->name('control-panel.control-core.nodes.store');
    });
