<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status' => 'healthy',
]))->name('health');

Route::get('/health/live', fn () => response()->json([
    'status' => 'alive',
]))->name('health.live');

Route::get('/health/ready', function () {
    try {
        DB::connection()->getPdo();

        if (config('cache.default') === 'redis') {
            Cache::store('redis')->get('health_check');
        }

        return response()->json(['status' => 'ready']);
    } catch (Throwable $exception) {
        report($exception);

        return response()->json(['status' => 'not ready'], 503);
    }
})->name('health.ready');

Route::get('/health/startup', function () {
    try {
        if (empty(config('app.key'))) {
            return response()->json(['status' => 'starting'], 503);
        }

        DB::connection()->getPdo();

        return response()->json(['status' => 'started']);
    } catch (Throwable $exception) {
        report($exception);

        return response()->json(['status' => 'starting'], 503);
    }
})->name('health.startup');

Route::get('/health/detailed', function () {
    abort_unless(auth()->user()?->hasRole('super_admin'), 403);

    return response()->json([
        'status' => 'healthy',
        'metrics' => [
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'version' => config('app.version', 'unknown'),
            ],
            'php' => [
                'version' => PHP_VERSION,
            ],
            'database' => [
                'connected' => true,
                'driver' => DB::connection()->getDriverName(),
            ],
            'cache' => [
                'driver' => config('cache.default'),
            ],
        ],
    ]);
})->middleware('auth')->name('health.detailed');
