<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources\StatusSnapshotResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MonitoringFilament\Resources\StatusSnapshotResource;

final class ListStatusSnapshots extends ListRecords
{
    protected static string $resource = StatusSnapshotResource::class;
}
