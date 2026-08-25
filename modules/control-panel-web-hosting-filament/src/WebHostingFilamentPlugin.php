<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\WebHostingFilament\Resources\DomainResource;
use Liberu\ControlPanel\WebHostingFilament\Resources\RuntimeVersionResource;
use Liberu\ControlPanel\WebHostingFilament\Resources\GitDeploymentResource;
use Liberu\ControlPanel\WebHostingFilament\Resources\PhpConfigurationResource;
use Liberu\ControlPanel\WebHostingFilament\Resources\VirtualHostResource;
use Liberu\ControlPanel\WebHostingFilament\Resources\WebServerResource;
use Liberu\ControlPanel\WebHostingFilament\Resources\SslCertificateResource;
use Liberu\ControlPanel\WebHostingFilament\Resources\HostingLogResource;
use Liberu\ControlPanel\WebHostingFilament\Resources\RedirectResource;
use Liberu\ControlPanel\WebHostingFilament\Resources\HostedApplicationResource;

final class WebHostingFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'control-panel-web-hosting-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([DomainResource::class, RuntimeVersionResource::class, GitDeploymentResource::class, PhpConfigurationResource::class, VirtualHostResource::class, WebServerResource::class, SslCertificateResource::class, HostingLogResource::class, RedirectResource::class, HostedApplicationResource::class]);
    }

    public function boot(Panel $panel): void {}
}
