<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreFilament\Resources\AuditEntryResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ControlCoreFilament\Resources\AuditEntryResource;

final class ListAuditEntries extends ListRecords
{
    protected static string $resource = AuditEntryResource::class;
}
