<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources\ComplianceStatusResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\SecurityFilament\Resources\ComplianceStatusResource;

final class CreateComplianceStatus extends CreateRecord
{
    protected static string $resource = ComplianceStatusResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
