<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources\ZoneResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\DnsFilament\Resources\ZoneResource;

final class ListZones extends ListRecords
{
    protected static string $resource = ZoneResource::class;
}
