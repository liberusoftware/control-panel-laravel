<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\SslCertificateResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\SslCertificateResource;

final class EditSslCertificate extends EditRecord
{
    protected static string $resource = SslCertificateResource::class;
}
