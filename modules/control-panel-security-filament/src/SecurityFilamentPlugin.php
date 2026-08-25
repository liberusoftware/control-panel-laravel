<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\SecurityFilament\Resources\HardeningControlResource;
use Liberu\ControlPanel\SecurityFilament\Resources\SecurityFindingResource;

final class SecurityFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'control-panel-security-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([SecurityFindingResource::class, HardeningControlResource::class]);
    }

    public function boot(Panel $panel): void {}
}
