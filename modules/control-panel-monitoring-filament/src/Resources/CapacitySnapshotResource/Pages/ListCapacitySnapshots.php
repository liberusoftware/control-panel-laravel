<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources\CapacitySnapshotResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MonitoringFilament\Resources\CapacitySnapshotResource;

final class ListCapacitySnapshots extends ListRecords
{
    protected static string $resource = CapacitySnapshotResource::class;
}
