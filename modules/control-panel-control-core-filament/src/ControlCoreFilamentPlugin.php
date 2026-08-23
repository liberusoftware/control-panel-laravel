<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;

final class ControlCoreFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'control-panel-control-core-filament';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}
