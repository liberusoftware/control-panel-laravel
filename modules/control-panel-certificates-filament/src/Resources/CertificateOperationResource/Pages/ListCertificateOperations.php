<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament\Resources\CertificateOperationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateOperationResource;

final class ListCertificateOperations extends ListRecords
{
    protected static string $resource = CertificateOperationResource::class;
}
