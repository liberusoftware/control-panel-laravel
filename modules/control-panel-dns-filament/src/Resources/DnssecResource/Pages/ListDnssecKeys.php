<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources\DnssecResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\DnsFilament\Resources\DnssecResource;

final class ListDnssecKeys extends ListRecords
{
    protected static string $resource = DnssecResource::class;
}
