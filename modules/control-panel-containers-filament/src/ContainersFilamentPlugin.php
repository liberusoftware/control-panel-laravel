<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerImageResource;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerLifecycleResource;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerLimitResource;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerNetworkResource;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerRegistryResource;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerSecretResource;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerVolumeResource;
use Liberu\ControlPanel\ContainersFilament\Resources\WorkloadResource;

final class ContainersFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'control-panel-containers-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            WorkloadResource::class,
            ContainerImageResource::class,
            ContainerRegistryResource::class,
            ContainerNetworkResource::class,
            ContainerVolumeResource::class,
            ContainerSecretResource::class,
            ContainerLimitResource::class,
            ContainerLifecycleResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
