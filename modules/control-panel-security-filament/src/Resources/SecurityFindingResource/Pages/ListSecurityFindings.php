<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources\SecurityFindingResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\SecurityFilament\Resources\SecurityFindingResource;

final class ListSecurityFindings extends ListRecords
{
    protected static string $resource = SecurityFindingResource::class;
}
