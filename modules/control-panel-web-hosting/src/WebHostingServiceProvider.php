<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\WebHosting\Actions\ActivateDomain;
use Liberu\ControlPanel\WebHosting\Actions\CreateDomain;
use Liberu\ControlPanel\WebHosting\Queries\ListDomains;

final class WebHostingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(ActivateDomain::class);
        $this->app->scoped(CreateDomain::class);
        $this->app->scoped(ListDomains::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
