<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationApi;

use Illuminate\Support\ServiceProvider;

final class ApiAutomationApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
