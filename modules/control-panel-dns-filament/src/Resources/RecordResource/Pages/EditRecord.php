<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources\RecordResource\Pages;

use Filament\Resources\Pages\EditRecord as BaseEditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Dns\Actions\UpdateRecord;
use Liberu\ControlPanel\Dns\Models\Record;
use Liberu\ControlPanel\DnsFilament\Resources\RecordResource;

final class EditRecord extends BaseEditRecord
{
    protected static string $resource = RecordResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Record $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->zone?->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateRecord::class)->execute($record, $data);
    }
}
