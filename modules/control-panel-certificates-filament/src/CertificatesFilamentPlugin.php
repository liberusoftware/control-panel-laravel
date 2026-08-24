<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateResource;

final class CertificatesFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'control-panel-certificates-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CertificateResource::class]);
    }

    public function boot(Panel $panel): void {}
}
