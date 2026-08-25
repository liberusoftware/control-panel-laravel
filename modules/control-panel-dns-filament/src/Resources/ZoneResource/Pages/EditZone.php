<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources\ZoneResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\DnsFilament\Resources\ZoneResource;

final class EditZone extends EditRecord
{
    protected static string $resource = ZoneResource::class;
}
