<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\DnsApi\Http\Controllers\ZoneController;

Route::prefix('api/v1/control-panel/dns')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/zones', [ZoneController::class, 'index'])->name('control-panel.dns.zones.index');
    Route::post('/zones', [ZoneController::class, 'store'])->name('control-panel.dns.zones.store');
    Route::post('/records', [ZoneController::class, 'record'])->name('control-panel.dns.records.store');
    Route::patch('/records/{record}', [ZoneController::class, 'updateRecord'])->name('control-panel.dns.records.update');
    Route::post('/records/bulk', [ZoneController::class, 'bulkRecords'])->name('control-panel.dns.records.bulk');
    Route::post('/checks', [ZoneController::class, 'check'])->name('control-panel.dns.checks.store');
    Route::post('/features', [ZoneController::class, 'feature'])->name('control-panel.dns.features.store');
    Route::post('zones/{zone}/suspend', [ZoneController::class, 'suspend'])->name('control-panel.dns.zones.suspend');
    Route::post('zones/{zone}/archive', [ZoneController::class, 'archive'])->name('control-panel.dns.zones.archive');
    Route::get('zones/{zone}', [ZoneController::class, 'show'])->name('control-panel.dns.show');
});
