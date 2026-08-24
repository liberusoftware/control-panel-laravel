<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files;

use Illuminate\Support\ServiceProvider;

final class FilesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
