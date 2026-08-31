<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament\Resources\CertificateDeploymentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateDeploymentResource;

final class ListCertificateDeployments extends ListRecords
{
    protected static string $resource = CertificateDeploymentResource::class;
}
