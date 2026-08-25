<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Databases\Actions\ConfigureRemoteAccess;
use Liberu\ControlPanel\Databases\Actions\CreateDatabaseBackup;
use Liberu\ControlPanel\Databases\Actions\CreateDatabaseUser;
use Liberu\ControlPanel\Databases\Actions\GrantDatabasePrivilege;
use Liberu\ControlPanel\Databases\Actions\RecordDatabaseHealth;
use Liberu\ControlPanel\Databases\Actions\RequestDatabaseUpgrade;
use Liberu\ControlPanel\Databases\Queries\ListDatabaseBackups;
use Liberu\ControlPanel\Databases\Queries\ListDatabases;

final class DatabasesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    public function register(): void
    {
        $this->app->scoped(CreateDatabaseUser::class);
        $this->app->scoped(GrantDatabasePrivilege::class);
        $this->app->scoped(CreateDatabaseBackup::class);
        $this->app->scoped(RecordDatabaseHealth::class);
        $this->app->scoped(RequestDatabaseUpgrade::class);
        $this->app->scoped(ConfigureRemoteAccess::class);
        $this->app->scoped(ListDatabases::class);
        $this->app->scoped(ListDatabaseBackups::class);
    }
}
