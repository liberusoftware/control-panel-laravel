<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns;

use Illuminate\Support\ServiceProvider;

final class DnsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
