<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\DnsFilament\Resources\DnsCheckResource;
use Liberu\ControlPanel\DnsFilament\Resources\DnsProviderResource;
use Liberu\ControlPanel\DnsFilament\Resources\DnssecResource;
use Liberu\ControlPanel\DnsFilament\Resources\DnsTemplateResource;
use Liberu\ControlPanel\DnsFilament\Resources\DnsValidationResource;
use Liberu\ControlPanel\DnsFilament\Resources\PropagationResource;
use Liberu\ControlPanel\DnsFilament\Resources\RecordResource;
use Liberu\ControlPanel\DnsFilament\Resources\ZoneResource;

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

    public function register(Panel $panel): void
    {
        $panel->resources([
            ZoneResource::class,
            RecordResource::class,
            DnsCheckResource::class,
            DnsTemplateResource::class,
            DnssecResource::class,
            DnsProviderResource::class,
            DnsValidationResource::class,
            PropagationResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
