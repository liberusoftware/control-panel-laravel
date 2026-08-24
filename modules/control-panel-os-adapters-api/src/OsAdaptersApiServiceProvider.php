<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersApi;

use Illuminate\Support\ServiceProvider;

final class OsAdaptersApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
