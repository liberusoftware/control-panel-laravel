<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsApi;

use Illuminate\Support\ServiceProvider;

final class BackupsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
