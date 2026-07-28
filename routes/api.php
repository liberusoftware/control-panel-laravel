<?php

use App\Http\Controllers\Api\DatabaseController;
use App\Http\Controllers\Api\DnsController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\ServiceStatusController;
use App\Http\Controllers\Api\SshController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VirtualHostController;
use App\Http\Controllers\Api\WebsiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // User endpoints
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('abilities:read');
    Route::get('/me', [UserController::class, 'me'])->middleware('abilities:read');
    Route::put('/me', [UserController::class, 'update'])->middleware('abilities:update');
    Route::get('/statistics', [UserController::class, 'statistics'])->middleware('abilities:read');
    
    // API Token management
    Route::post('/tokens', [UserController::class, 'createToken'])->middleware('abilities:update');
    Route::get('/tokens', [UserController::class, 'tokens'])->middleware('abilities:read');
    Route::delete('/tokens/{tokenId}', [UserController::class, 'revokeToken'])->middleware('abilities:delete');

    // Virtual Host management
    Route::apiResource('virtual-hosts', VirtualHostController::class)
        ->middlewareFor(['index', 'show'], 'abilities:read')
        ->middlewareFor('store', 'abilities:create')
        ->middlewareFor('update', 'abilities:update')
        ->middlewareFor('destroy', 'abilities:delete');

    // Website management
    Route::apiResource('websites', WebsiteController::class)
        ->middlewareFor(['index', 'show'], 'abilities:read')
        ->middlewareFor('store', 'abilities:create')
        ->middlewareFor('update', 'abilities:update')
        ->middlewareFor('destroy', 'abilities:delete');
    Route::get('/websites/{website}/performance', [WebsiteController::class, 'performance'])->middleware('abilities:read');
    Route::get('/websites-statistics', [WebsiteController::class, 'statistics'])->middleware('abilities:read');

    // Database management
    Route::apiResource('databases', DatabaseController::class)->except(['update'])
        ->middlewareFor(['index', 'show'], 'abilities:read')
        ->middlewareFor('store', 'abilities:create')
        ->middlewareFor('destroy', 'abilities:delete');

    // Email management
    Route::apiResource('emails', EmailController::class)
        ->middlewareFor(['index', 'show'], 'abilities:read')
        ->middlewareFor('store', 'abilities:create')
        ->middlewareFor('update', 'abilities:update')
        ->middlewareFor('destroy', 'abilities:delete');

    // DNS management
    Route::apiResource('dns', DnsController::class)->parameters(['dns' => 'dnsSetting'])
        ->middlewareFor(['index', 'show'], 'abilities:read')
        ->middlewareFor('store', 'abilities:create')
        ->middlewareFor('update', 'abilities:update')
        ->middlewareFor('destroy', 'abilities:delete');
    Route::post('/dns/bulk', [DnsController::class, 'bulkStore'])->middleware('abilities:create');
    Route::post('/dns/validate', [DnsController::class, 'validateRecord'])->middleware('abilities:read');
    Route::get('/domains/{domain}/dns/test', [DnsController::class, 'testResolution'])->middleware('abilities:read');
    Route::get('/domains/{domain}/dns/propagation', [DnsController::class, 'checkPropagation'])->middleware('abilities:read');

    // SSH Key management
    Route::post('/ssh/generate-keypair', [SshController::class, 'generateKeyPair'])->middleware('abilities:create');
    Route::post('/ssh/domains/{domain}/deploy-key', [SshController::class, 'deployKeyToDomain'])->middleware('abilities:update');
    Route::post('/ssh/servers/{server}/deploy-key', [SshController::class, 'deployKeyToServer'])->middleware('abilities:update');
    Route::post('/ssh/servers/{server}/test-connection', [SshController::class, 'testConnection'])->middleware('abilities:read');
    Route::post('/ssh/credentials', [SshController::class, 'createCredential'])->middleware('abilities:create');

    // Service Status (Standalone mode)
    Route::get('/services/status', [ServiceStatusController::class, 'checkAll'])->middleware('abilities:read');
    Route::get('/services/missing', [ServiceStatusController::class, 'missing'])->middleware('abilities:read');
    Route::get('/services/stopped', [ServiceStatusController::class, 'stopped'])->middleware('abilities:read');
    Route::get('/services/install-commands', [ServiceStatusController::class, 'installCommands'])->middleware('abilities:read');
    Route::get('/services/{service}/status', [ServiceStatusController::class, 'checkService'])->middleware('abilities:read');
});

// Webhook routes for Git deployments (no auth required, validated via signature)
Route::prefix('webhooks')->group(function () {
    Route::post('github/{deployment}', [WebhookController::class, 'github'])->name('webhooks.github');
    Route::post('gitlab/{deployment}', [WebhookController::class, 'gitlab'])->name('webhooks.gitlab');
    Route::post('generic/{deployment}', [WebhookController::class, 'generic'])->name('webhooks.generic');
});
