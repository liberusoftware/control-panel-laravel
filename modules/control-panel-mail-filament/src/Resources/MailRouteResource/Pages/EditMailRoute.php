<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\MailRouteResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Mail\Actions\UpdateMailRoute;
use Liberu\ControlPanel\Mail\Models\MailRoute;
use Liberu\ControlPanel\MailFilament\Resources\MailRouteResource;

final class EditMailRoute extends EditRecord
{
    protected static string $resource = MailRouteResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var MailRoute $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateMailRoute::class)->execute($record, $data);
    }
}
