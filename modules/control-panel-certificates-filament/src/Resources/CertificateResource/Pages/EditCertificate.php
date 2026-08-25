<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament\Resources\CertificateResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateResource;

final class EditCertificate extends EditRecord
{
    protected static string $resource = CertificateResource::class;
}
