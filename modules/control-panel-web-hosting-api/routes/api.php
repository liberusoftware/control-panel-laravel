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
        Route::get('/deployments', [DomainController::class, 'deployments'])->name('control-panel.web-hosting.deployments.index');
        Route::post('/domains/{domain}/deployments', [DomainController::class, 'deployment'])->name('control-panel.web-hosting.deployments.store');
        Route::put('/domains/{domain}/php-configuration', [DomainController::class, 'phpConfiguration'])->name('control-panel.web-hosting.php-configuration.update');
        Route::post('/resources', [DomainController::class, 'resourceRecord'])->name('control-panel.web-hosting.resources.store');
        Route::get('/resources/{kind}', [DomainController::class, 'resources'])->name('control-panel.web-hosting.resources.index');
    });
