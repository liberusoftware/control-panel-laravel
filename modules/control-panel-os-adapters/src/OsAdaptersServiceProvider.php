<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\OsAdapters\Actions\CreateFirewallRule;
use Liberu\ControlPanel\OsAdapters\Actions\DeleteFirewallRule;
use Liberu\ControlPanel\OsAdapters\Actions\RecordOsResource;
use Liberu\ControlPanel\OsAdapters\Actions\RecordSupportMatrix;
use Liberu\ControlPanel\OsAdapters\Actions\RegisterOsAdapter;
use Liberu\ControlPanel\OsAdapters\Actions\UpdateFirewallRule;
use Liberu\ControlPanel\OsAdapters\Actions\UpdateOsService;
use Liberu\ControlPanel\OsAdapters\Queries\ListOsAdapters;
use Liberu\ControlPanel\OsAdapters\Queries\ServiceStatusReport;

final class OsAdaptersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(RegisterOsAdapter::class);
        $this->app->scoped(ListOsAdapters::class);
        $this->app->scoped(RecordOsResource::class);
        $this->app->scoped(RecordSupportMatrix::class);
        $this->app->scoped(UpdateOsService::class);
        $this->app->scoped(CreateFirewallRule::class);
        $this->app->scoped(UpdateFirewallRule::class);
        $this->app->scoped(DeleteFirewallRule::class);
        $this->app->scoped(ServiceStatusReport::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
