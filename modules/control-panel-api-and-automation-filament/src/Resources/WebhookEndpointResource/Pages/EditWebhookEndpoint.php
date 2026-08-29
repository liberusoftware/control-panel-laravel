<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\WebhookEndpointResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\ApiAutomation\Actions\UpdateWebhook;
use Liberu\ControlPanel\ApiAutomation\Models\WebhookEndpoint;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\WebhookEndpointResource;

final class EditWebhookEndpoint extends EditRecord
{
    protected static string $resource = WebhookEndpointResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var WebhookEndpoint $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateWebhook::class)->execute($record, $data);
    }
}
