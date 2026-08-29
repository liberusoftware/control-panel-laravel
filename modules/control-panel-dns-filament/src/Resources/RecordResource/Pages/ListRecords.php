<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources\RecordResource\Pages;

use Filament\Resources\Pages\ListRecords as BaseListRecords;
use Liberu\ControlPanel\DnsFilament\Resources\RecordResource;

final class ListRecords extends BaseListRecords
{
    protected static string $resource = RecordResource::class;
}
