<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\KubernetesFilament\Resources\ClusterResource;
use Liberu\ControlPanel\KubernetesFilament\Resources\HelmReleaseResource;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesAutoscalerResource;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesClusterViewResource;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesIngressResource;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNamespaceResource;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesRbacBindingResource;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesStorageClaimResource;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesUpgradeResource;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesWorkloadResource;

final class KubernetesFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'control-panel-kubernetes-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            ClusterResource::class,
            KubernetesNodeResource::class,
            KubernetesNamespaceResource::class,
            KubernetesRbacBindingResource::class,
            KubernetesWorkloadResource::class,
            KubernetesIngressResource::class,
            HelmReleaseResource::class,
            KubernetesStorageClaimResource::class,
            KubernetesAutoscalerResource::class,
            KubernetesUpgradeResource::class,
            KubernetesClusterViewResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
