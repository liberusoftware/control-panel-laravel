<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreFilament\Resources\OperationLockResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ControlCoreFilament\Resources\OperationLockResource;

final class ListOperationLocks extends ListRecords
{
    protected static string $resource = OperationLockResource::class;
}
