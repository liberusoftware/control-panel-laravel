<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationTemplateResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationTemplateResource;

final class CreateAutomationTemplate extends CreateRecord
{
    protected static string $resource = AutomationTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
