<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\ControlPanel\ApiAutomationApi\Http\Controllers\AutomationController;

Route::prefix('api/v1/control-panel/api-and-automation')->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/', [AutomationController::class, 'index'])->name('control-panel.api-and-automation.index');
    Route::post('/', [AutomationController::class, 'store'])->name('control-panel.api-and-automation.store');
    Route::post('credentials', [AutomationController::class, 'credential'])->name('control-panel.api-and-automation.credentials.store');
    Route::post('credentials/{credential}/revoke', [AutomationController::class, 'revokeCredential'])->name('control-panel.api-and-automation.credentials.revoke');
    Route::post('webhooks', [AutomationController::class, 'webhook'])->name('control-panel.api-and-automation.webhooks.store');
    Route::patch('webhooks/{webhook}', [AutomationController::class, 'updateWebhook'])->name('control-panel.api-and-automation.webhooks.update');
    Route::post('webhooks/{webhook}/pause', [AutomationController::class, 'pauseWebhook'])->name('control-panel.api-and-automation.webhooks.pause');
    Route::post('webhooks/{webhook}/resume', [AutomationController::class, 'resumeWebhook'])->name('control-panel.api-and-automation.webhooks.resume');
    Route::post('templates/{template}/runs', [AutomationController::class, 'run'])->name('control-panel.api-and-automation.runs.store');
    Route::post('templates', [AutomationController::class, 'template'])->name('control-panel.api-and-automation.templates.store');
    Route::post('schedules', [AutomationController::class, 'schedule'])->name('control-panel.api-and-automation.schedules.store');
    Route::post('commands', [AutomationController::class, 'command'])->name('control-panel.api-and-automation.commands.store');
    Route::post('billing-events', [AutomationController::class, 'billingEvent'])->name('control-panel.api-and-automation.billing-events.store');
    Route::get('{automation}', [AutomationController::class, 'show'])->name('control-panel.api-and-automation.show');
});
