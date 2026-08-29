<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Backups\Actions\CreateDestination;
use Liberu\ControlPanel\Backups\Actions\CreateSchedule;
use Liberu\ControlPanel\Backups\Actions\DeleteDestination;
use Liberu\ControlPanel\Backups\Actions\DeletePolicy;
use Liberu\ControlPanel\Backups\Actions\RecordBackupFeature;
use Liberu\ControlPanel\Backups\Actions\RequestRestore;
use Liberu\ControlPanel\Backups\Actions\UpdateDestination;
use Liberu\ControlPanel\Backups\Actions\UpdatePolicy;

final class BackupsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CreateDestination::class);
        $this->app->scoped(DeleteDestination::class);
        $this->app->scoped(DeletePolicy::class);
        $this->app->scoped(CreateSchedule::class);
        $this->app->scoped(RequestRestore::class);
        $this->app->scoped(RecordBackupFeature::class);
        $this->app->scoped(UpdatePolicy::class);
        $this->app->scoped(UpdateDestination::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
