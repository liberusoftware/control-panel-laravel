<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Kubernetes\Actions\RecordKubernetesResource;
use Liberu\ControlPanel\Kubernetes\Actions\RegisterCluster;
use Liberu\ControlPanel\Kubernetes\Actions\RegisterKubernetesAsset;
use Liberu\ControlPanel\Kubernetes\Queries\ListClusters;

final class KubernetesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(RegisterCluster::class);
        $this->app->scoped(ListClusters::class);
        $this->app->scoped(RecordKubernetesResource::class);
        $this->app->scoped(RegisterKubernetesAsset::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
