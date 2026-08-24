<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases;

use Illuminate\Support\ServiceProvider;

final class DatabasesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
