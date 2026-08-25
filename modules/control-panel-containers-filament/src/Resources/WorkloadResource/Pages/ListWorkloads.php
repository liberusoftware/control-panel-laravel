<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources\WorkloadResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ContainersFilament\Resources\WorkloadResource;

final class ListWorkloads extends ListRecords
{
    protected static string $resource = WorkloadResource::class;
}
