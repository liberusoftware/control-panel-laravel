<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\DnsApi\Http\Controllers\ZoneController;

Route::prefix('api/v1/control-panel/dns')->middleware(['api', 'auth:sanctum'])->group(function (): void {
    Route::get('/zones', [ZoneController::class, 'index'])->name('control-panel.dns.zones.index');
    Route::post('/zones', [ZoneController::class, 'store'])->name('control-panel.dns.zones.store');
});
