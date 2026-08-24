<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Containers;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Containers\Actions\RegisterWorkload;
use Liberu\ControlPanel\Containers\Queries\ListWorkloads;

final class ContainersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(RegisterWorkload::class);
        $this->app->scoped(ListWorkloads::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
