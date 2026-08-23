<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Accounts\Actions\CreateAccount;
use Liberu\ControlPanel\Accounts\Actions\SuspendAccount;
use Liberu\ControlPanel\Accounts\Services\QuotaGuard;

final class AccountsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CreateAccount::class);
        $this->app->scoped(SuspendAccount::class);
        $this->app->scoped(QuotaGuard::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
