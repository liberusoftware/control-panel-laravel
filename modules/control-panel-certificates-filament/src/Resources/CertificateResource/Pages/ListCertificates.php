<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament\Resources\CertificateResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateResource;

final class ListCertificates extends ListRecords
{
    protected static string $resource = CertificateResource::class;
}
