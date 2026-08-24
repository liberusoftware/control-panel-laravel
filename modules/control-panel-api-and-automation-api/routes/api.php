<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\ApiAutomationApi\Http\Controllers\AutomationController;

Route::prefix('api/v1/control-panel/api-and-automation')->middleware(['api', 'auth:sanctum'])->group(function (): void {
    Route::get('/', [AutomationController::class, 'index'])->name('control-panel.api-and-automation.index');
    Route::post('/', [AutomationController::class, 'store'])->name('control-panel.api-and-automation.store');
});
