<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources\DnsTemplateResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\DnsFilament\Resources\DnsTemplateResource;

final class ListDnsTemplates extends ListRecords
{
    protected static string $resource = DnsTemplateResource::class;
}
