<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\CertificatesFilament\Resources\AcmeAccountResource;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateDeploymentResource;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateExpiryAlertResource;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateOperationResource;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateRenewalResource;
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
        $panel->resources([
            AcmeAccountResource::class,
            CertificateResource::class,
            CertificateOperationResource::class,
            CertificateDeploymentResource::class,
            CertificateRenewalResource::class,
            CertificateExpiryAlertResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
