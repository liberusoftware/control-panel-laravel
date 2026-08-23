<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsApi;

use Illuminate\Support\ServiceProvider;

final class AccountsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
