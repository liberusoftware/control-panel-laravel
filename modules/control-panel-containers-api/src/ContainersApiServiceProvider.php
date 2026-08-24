<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersApi;

use Illuminate\Support\ServiceProvider;

final class ContainersApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
