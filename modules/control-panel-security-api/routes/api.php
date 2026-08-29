<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\SecurityApi\Http\Controllers\SecurityFindingController;

Route::prefix('api/v1/control-panel/security')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [SecurityFindingController::class, 'index'])->name('control-panel.security.index');
    Route::post('/', [SecurityFindingController::class, 'store'])->name('control-panel.security.store');
    Route::post('{finding}/resolve', [SecurityFindingController::class, 'resolve'])->name('control-panel.security.resolve');
    Route::patch('{finding}', [SecurityFindingController::class, 'update'])->name('control-panel.security.update');
    Route::post('hardening', [SecurityFindingController::class, 'hardening'])->name('control-panel.security.hardening.store');
    Route::post('patches', [SecurityFindingController::class, 'patch'])->name('control-panel.security.patches.store');
    Route::post('mfa-rbac', [SecurityFindingController::class, 'policy'])->name('control-panel.security.mfa-rbac.store');
    Route::post('secrets', [SecurityFindingController::class, 'secret'])->name('control-panel.security.secrets.store');
    Route::post('malware-scans', [SecurityFindingController::class, 'malware'])->name('control-panel.security.malware.store');
    Route::post('intrusion-controls', [SecurityFindingController::class, 'intrusion'])->name('control-panel.security.intrusion.store');
    Route::post('compliance', [SecurityFindingController::class, 'compliance'])->name('control-panel.security.compliance.store');
    Route::get('{finding}', [SecurityFindingController::class, 'show'])->name('control-panel.security.show');
});
