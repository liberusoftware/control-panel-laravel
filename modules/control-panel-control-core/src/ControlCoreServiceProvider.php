<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNode;
use Liberu\ControlPanel\ControlCore\Queries\ListNodes;

final class ControlCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(RegisterNode::class);
        $this->app->scoped(ListNodes::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
