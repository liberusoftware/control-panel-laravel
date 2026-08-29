<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Databases\Actions\UpdateDatabase;
use Liberu\ControlPanel\Databases\Models\Database;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseResource;

final class EditDatabase extends EditRecord
{
    protected static string $resource = DatabaseResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Database $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateDatabase::class)->execute($record, $data);
    }
}
