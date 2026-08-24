<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups;

use Illuminate\Support\ServiceProvider;

final class BackupsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
