<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\WebhookEndpointResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\WebhookEndpointResource;

final class CreateWebhookEndpoint extends CreateRecord
{
    protected static string $resource = WebhookEndpointResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
