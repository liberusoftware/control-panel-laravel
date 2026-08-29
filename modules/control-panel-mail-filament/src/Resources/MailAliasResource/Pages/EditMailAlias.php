<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\MailAliasResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Mail\Actions\UpdateMailAlias;
use Liberu\ControlPanel\Mail\Models\MailAlias;
use Liberu\ControlPanel\MailFilament\Resources\MailAliasResource;

final class EditMailAlias extends EditRecord
{
    protected static string $resource = MailAliasResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var MailAlias $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateMailAlias::class)->execute($record, $data);
    }
}
