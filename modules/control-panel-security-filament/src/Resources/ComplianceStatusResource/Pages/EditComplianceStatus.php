<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources\ComplianceStatusResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\SecurityFilament\Resources\ComplianceStatusResource;

final class EditComplianceStatus extends EditRecord
{
    protected static string $resource = ComplianceStatusResource::class;
}
