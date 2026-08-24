<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\ControlCore\Actions\AcquireOperationLock;
use Liberu\ControlPanel\ControlCore\Actions\CreateOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\RecordInventory;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNode;
use Liberu\ControlPanel\ControlCore\Queries\ListInventory;
use Liberu\ControlPanel\ControlCore\Queries\ListNodes;
use Liberu\ControlPanel\ControlCore\Queries\ListOperationTasks;

final class ControlCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(RegisterNode::class);
        $this->app->scoped(CreateOperationTask::class);
        $this->app->scoped(RecordInventory::class);
        $this->app->scoped(AcquireOperationLock::class);
        $this->app->scoped(ListNodes::class);
        $this->app->scoped(ListOperationTasks::class);
        $this->app->scoped(ListInventory::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
