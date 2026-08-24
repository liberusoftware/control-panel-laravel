<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\DnsApi\Http\Controllers\ZoneController;

Route::prefix('api/v1/control-panel/dns')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/zones', [ZoneController::class, 'index'])->name('control-panel.dns.zones.index');
    Route::post('/zones', [ZoneController::class, 'store'])->name('control-panel.dns.zones.store');
    Route::post('/records', [ZoneController::class, 'record'])->name('control-panel.dns.records.store');
    Route::post('/checks', [ZoneController::class, 'check'])->name('control-panel.dns.checks.store');
});
