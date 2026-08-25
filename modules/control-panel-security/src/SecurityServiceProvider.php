<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Security\Actions\RecordFinding;
use Liberu\ControlPanel\Security\Actions\RecordSecurityResource;
use Liberu\ControlPanel\Security\Actions\ResolveSecurityFinding;
use Liberu\ControlPanel\Security\Actions\StoreSecret;
use Liberu\ControlPanel\Security\Queries\ListFindings;

final class SecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(RecordFinding::class);
        $this->app->scoped(ResolveSecurityFinding::class);
        $this->app->scoped(ListFindings::class);
        $this->app->scoped(RecordSecurityResource::class);
        $this->app->scoped(StoreSecret::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
