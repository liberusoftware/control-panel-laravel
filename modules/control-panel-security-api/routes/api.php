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
    Route::get('mfa-rbac', [SecurityFindingController::class, 'policies'])->name('control-panel.security.mfa-rbac.index');
    Route::post('secrets', [SecurityFindingController::class, 'secret'])->name('control-panel.security.secrets.store');
    Route::get('secrets', [SecurityFindingController::class, 'secrets'])->name('control-panel.security.secrets.index');
    Route::post('malware-scans', [SecurityFindingController::class, 'malware'])->name('control-panel.security.malware.store');
    Route::get('malware-scans', [SecurityFindingController::class, 'malwareScans'])->name('control-panel.security.malware.index');
    Route::post('intrusion-controls', [SecurityFindingController::class, 'intrusion'])->name('control-panel.security.intrusion.store');
    Route::get('intrusion-controls', [SecurityFindingController::class, 'intrusionControls'])->name('control-panel.security.intrusion.index');
    Route::get('patches', [SecurityFindingController::class, 'patches'])->name('control-panel.security.patches.index');
    Route::post('fail2ban', [SecurityFindingController::class, 'fail2ban'])->name('control-panel.security.fail2ban.store');
    Route::get('fail2ban/{jail}/bans', [SecurityFindingController::class, 'listFail2banBans'])->name('control-panel.security.fail2ban.bans.index');
    Route::post('fail2ban/{jail}/bans', [SecurityFindingController::class, 'fail2banBans'])->name('control-panel.security.fail2ban.bans.store');
    Route::post('fail2ban/bans/{ban}/unban', [SecurityFindingController::class, 'unbanFail2ban'])->name('control-panel.security.fail2ban.bans.unban');
    Route::post('compliance', [SecurityFindingController::class, 'compliance'])->name('control-panel.security.compliance.store');
    Route::get('{finding}', [SecurityFindingController::class, 'show'])->name('control-panel.security.show');
});
