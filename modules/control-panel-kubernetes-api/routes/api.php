<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\KubernetesApi\Http\Controllers\ClusterController;

Route::prefix('api/v1/control-panel/kubernetes')->middleware(['api', 'auth:sanctum'])->group(function (): void {
    Route::get('/', [ClusterController::class, 'index'])->name('control-panel.kubernetes.index');
    Route::post('/', [ClusterController::class, 'store'])->name('control-panel.kubernetes.store');
});
