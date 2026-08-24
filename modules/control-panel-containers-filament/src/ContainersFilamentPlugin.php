<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;

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

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}
