<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\KubernetesFilament\Resources\ClusterResource;

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
        $panel->resources([ClusterResource::class]);
    }

    public function boot(Panel $panel): void {}
}
