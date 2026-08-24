<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Databases\Actions\CreateDatabaseUser;
use Liberu\ControlPanel\Databases\Actions\GrantDatabasePrivilege;

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
    }
}
