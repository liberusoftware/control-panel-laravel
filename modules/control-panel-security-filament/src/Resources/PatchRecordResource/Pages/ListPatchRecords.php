<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources\PatchRecordResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\SecurityFilament\Resources\PatchRecordResource;

final class ListPatchRecords extends ListRecords
{
    protected static string $resource = PatchRecordResource::class;
}
