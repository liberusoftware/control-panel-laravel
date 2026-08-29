<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\MailAccountResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Mail\Actions\UpdateMailAccount;
use Liberu\ControlPanel\Mail\Models\MailAccount;
use Liberu\ControlPanel\MailFilament\Resources\MailAccountResource;

final class EditMailAccount extends EditRecord
{
    protected static string $resource = MailAccountResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var MailAccount $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateMailAccount::class)->execute($record, $data);
    }
}
