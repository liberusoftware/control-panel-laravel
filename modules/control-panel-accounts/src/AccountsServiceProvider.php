<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Accounts\Actions\CreateAccount;
use Liberu\ControlPanel\Accounts\Actions\CreateHostingPackage;
use Liberu\ControlPanel\Accounts\Actions\DelegateAccount;
use Liberu\ControlPanel\Accounts\Actions\SuspendAccount;
use Liberu\ControlPanel\Accounts\Actions\UpdateBranding;
use Liberu\ControlPanel\Accounts\Actions\ActivateAccount;
use Liberu\ControlPanel\Accounts\Queries\ListAccounts;
use Liberu\ControlPanel\Accounts\Services\QuotaGuard;
use Liberu\ControlPanel\Accounts\Actions\UpdateHostingPackage;
use Liberu\ControlPanel\Accounts\Actions\RevokeDelegation;

final class AccountsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CreateAccount::class);
        $this->app->scoped(CreateHostingPackage::class);
        $this->app->scoped(DelegateAccount::class);
        $this->app->scoped(SuspendAccount::class);
        $this->app->scoped(UpdateBranding::class);
        $this->app->scoped(ActivateAccount::class);
        $this->app->scoped(ListAccounts::class);
        $this->app->scoped(QuotaGuard::class);
        $this->app->scoped(UpdateHostingPackage::class);
        $this->app->scoped(RevokeDelegation::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
