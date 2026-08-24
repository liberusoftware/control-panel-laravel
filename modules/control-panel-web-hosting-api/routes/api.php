<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\WebHostingApi\Http\Controllers\DomainController;

Route::prefix('api/v1/control-panel/web-hosting')
    ->middleware(['api', 'auth:sanctum', 'throttle:60,1'])
    ->group(function (): void {
        Route::get('/domains', [DomainController::class, 'index'])->name('control-panel.web-hosting.domains.index');
        Route::post('/domains', [DomainController::class, 'store'])->name('control-panel.web-hosting.domains.store');
        Route::post('/domains/{domain}/virtual-hosts', [DomainController::class, 'virtualHost'])->name('control-panel.web-hosting.virtual-hosts.store');
        Route::post('/domains/{domain}/redirects', [DomainController::class, 'redirect'])->name('control-panel.web-hosting.redirects.store');
        Route::post('/domains/{domain}/certificates', [DomainController::class, 'certificate'])->name('control-panel.web-hosting.certificates.store');
    });
