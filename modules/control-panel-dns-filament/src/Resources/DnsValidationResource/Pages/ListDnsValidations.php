<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources\DnsValidationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\DnsFilament\Resources\DnsValidationResource;

final class ListDnsValidations extends ListRecords
{
    protected static string $resource = DnsValidationResource::class;
}
