<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament\Resources\CertificateExpiryAlertResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateExpiryAlertResource;

final class ListCertificateExpiryAlerts extends ListRecords
{
    protected static string $resource = CertificateExpiryAlertResource::class;
}
