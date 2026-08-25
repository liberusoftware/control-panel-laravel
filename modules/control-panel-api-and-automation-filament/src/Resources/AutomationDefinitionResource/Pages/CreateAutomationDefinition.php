<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationDefinitionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationDefinitionResource;

final class CreateAutomationDefinition extends CreateRecord
{
    protected static string $resource = AutomationDefinitionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
