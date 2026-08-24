<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;

final class DnsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'control-panel-dns-filament';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}
