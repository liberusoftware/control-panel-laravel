<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterAutomation;
use Liberu\ControlPanel\ApiAutomation\Queries\ListAutomations;

final class ApiAutomationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(RegisterAutomation::class);
        $this->app->scoped(ListAutomations::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
