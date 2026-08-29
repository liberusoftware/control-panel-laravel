<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Monitoring;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Monitoring\Actions\DeleteMaintenanceWindow;
use Liberu\ControlPanel\Monitoring\Actions\RecordMonitoringEvent;
use Liberu\ControlPanel\Monitoring\Actions\RecordMonitoringResource;
use Liberu\ControlPanel\Monitoring\Actions\RegisterMonitor;
use Liberu\ControlPanel\Monitoring\Actions\ResolveMonitoringEvent;
use Liberu\ControlPanel\Monitoring\Actions\UpdateMaintenanceWindow;
use Liberu\ControlPanel\Monitoring\Queries\ListMonitors;

final class MonitoringServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(DeleteMaintenanceWindow::class);
        $this->app->scoped(RegisterMonitor::class);
        $this->app->scoped(ListMonitors::class);
        $this->app->scoped(RecordMonitoringEvent::class);
        $this->app->scoped(RecordMonitoringResource::class);
        $this->app->scoped(ResolveMonitoringEvent::class);
        $this->app->scoped(UpdateMaintenanceWindow::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
