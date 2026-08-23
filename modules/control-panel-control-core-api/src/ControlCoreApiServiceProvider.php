<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreApi;

use Illuminate\Support\ServiceProvider;

final class ControlCoreApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
