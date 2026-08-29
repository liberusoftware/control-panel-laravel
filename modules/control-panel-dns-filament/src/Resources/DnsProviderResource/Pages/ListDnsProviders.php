<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources\DnsProviderResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\DnsFilament\Resources\DnsProviderResource;

final class ListDnsProviders extends ListRecords
{
    protected static string $resource = DnsProviderResource::class;
}
