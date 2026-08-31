<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament\Resources\CertificateRenewalResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateRenewalResource;

final class ListCertificateRenewals extends ListRecords
{
    protected static string $resource = CertificateRenewalResource::class;
}
