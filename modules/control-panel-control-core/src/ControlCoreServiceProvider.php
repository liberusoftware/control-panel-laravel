<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\ControlCore\Actions\AcquireOperationLock;
use Liberu\ControlPanel\ControlCore\Actions\CreateOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\DecommissionNode;
use Liberu\ControlPanel\ControlCore\Actions\ExpireNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\GenerateSshKeyPair;
use Liberu\ControlPanel\ControlCore\Actions\RecordInventory;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNode;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\ReleaseOperationLock;
use Liberu\ControlPanel\ControlCore\Actions\RevokeNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\SyncNodeCapabilities;
use Liberu\ControlPanel\ControlCore\Actions\TransitionOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\UpdateDesiredState;
use Liberu\ControlPanel\ControlCore\Actions\UpdateNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\WriteAuditEntry;
use Liberu\ControlPanel\ControlCore\Queries\ListAuditEntries;
use Liberu\ControlPanel\ControlCore\Queries\ListInventory;
use Liberu\ControlPanel\ControlCore\Queries\ListNodes;
use Liberu\ControlPanel\ControlCore\Queries\ListOperationTasks;

final class ControlCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(RegisterNode::class);
        $this->app->scoped(CreateOperationTask::class);
        $this->app->scoped(DecommissionNode::class);
        $this->app->scoped(ExpireNodeCredential::class);
        $this->app->scoped(GenerateSshKeyPair::class);
        $this->app->scoped(RecordInventory::class);
        $this->app->scoped(AcquireOperationLock::class);
        $this->app->scoped(ListNodes::class);
        $this->app->scoped(ListOperationTasks::class);
        $this->app->scoped(ListInventory::class);
        $this->app->scoped(SyncNodeCapabilities::class);
        $this->app->scoped(UpdateDesiredState::class);
        $this->app->scoped(UpdateNodeCredential::class);
        $this->app->scoped(TransitionOperationTask::class);
        $this->app->scoped(WriteAuditEntry::class);
        $this->app->scoped(ListAuditEntries::class);
        $this->app->scoped(RegisterNodeCredential::class);
        $this->app->scoped(RevokeNodeCredential::class);
        $this->app->scoped(ReleaseOperationLock::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
