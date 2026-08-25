<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\OsAdapters\Actions\RecordOsResource;
use Liberu\ControlPanel\OsAdapters\Actions\RecordSupportMatrix;
use Liberu\ControlPanel\OsAdapters\Actions\RegisterOsAdapter;
use Liberu\ControlPanel\OsAdapters\Queries\ListOsAdapters;

final class OsAdaptersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(RegisterOsAdapter::class);
        $this->app->scoped(ListOsAdapters::class);
        $this->app->scoped(RecordOsResource::class);
        $this->app->scoped(RecordSupportMatrix::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
