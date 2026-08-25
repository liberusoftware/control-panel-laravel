<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources\ComplianceStatusResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\SecurityFilament\Resources\ComplianceStatusResource;

final class ListComplianceStatuses extends ListRecords
{
    protected static string $resource = ComplianceStatusResource::class;
}
