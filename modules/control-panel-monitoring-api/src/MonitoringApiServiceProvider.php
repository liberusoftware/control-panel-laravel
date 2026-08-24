<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringApi;

use Illuminate\Support\ServiceProvider;

final class MonitoringApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
