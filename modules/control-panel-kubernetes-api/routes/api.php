<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\KubernetesApi\Http\Controllers\ClusterController;

Route::prefix('api/v1/control-panel/kubernetes')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [ClusterController::class, 'index'])->name('control-panel.kubernetes.index');
    Route::post('/', [ClusterController::class, 'store'])->name('control-panel.kubernetes.store');
    Route::post('/resources', [ClusterController::class, 'resourceRecord'])->name('control-panel.kubernetes.resources.store');
    Route::post('/assets', [ClusterController::class, 'asset'])->name('control-panel.kubernetes.assets.store');
    Route::post('{cluster}/suspend', [ClusterController::class, 'suspend'])->name('control-panel.kubernetes.suspend');
    Route::post('{cluster}/archive', [ClusterController::class, 'archive'])->name('control-panel.kubernetes.archive');
    Route::get('{cluster}', [ClusterController::class, 'show'])->name('control-panel.kubernetes.show');
});
