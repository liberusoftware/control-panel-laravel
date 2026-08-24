<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityApi;

use Illuminate\Support\ServiceProvider;

final class SecurityApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
