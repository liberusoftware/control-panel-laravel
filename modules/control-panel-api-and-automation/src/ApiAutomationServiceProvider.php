<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterAutomation;
use Liberu\ControlPanel\ApiAutomation\Queries\ListAutomations;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterApiCredential;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\StartOrchestration;
use Liberu\ControlPanel\ApiAutomation\Queries\ListWebhooks;

final class ApiAutomationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(RegisterAutomation::class);
        $this->app->scoped(ListAutomations::class);
        $this->app->scoped(RegisterApiCredential::class);
        $this->app->scoped(RegisterWebhook::class);
        $this->app->scoped(StartOrchestration::class);
        $this->app->scoped(ListWebhooks::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
