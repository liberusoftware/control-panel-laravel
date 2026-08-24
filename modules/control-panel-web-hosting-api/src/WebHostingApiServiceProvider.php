<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingApi;

use Illuminate\Support\ServiceProvider;

final class WebHostingApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
