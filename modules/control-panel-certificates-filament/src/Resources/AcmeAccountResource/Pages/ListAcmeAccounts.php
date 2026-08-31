<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament\Resources\AcmeAccountResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\CertificatesFilament\Resources\AcmeAccountResource;

final class ListAcmeAccounts extends ListRecords
{
    protected static string $resource = AcmeAccountResource::class;
}
