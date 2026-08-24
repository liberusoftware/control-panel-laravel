<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsApi;

use Illuminate\Support\ServiceProvider;

final class DnsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
