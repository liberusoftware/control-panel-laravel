<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources\WorkloadResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\ContainersFilament\Resources\WorkloadResource;

final class EditWorkload extends EditRecord
{
    protected static string $resource = WorkloadResource::class;
}
