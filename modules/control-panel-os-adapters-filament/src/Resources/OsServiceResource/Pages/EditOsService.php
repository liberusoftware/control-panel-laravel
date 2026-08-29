<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources\OsServiceResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\OsAdapters\Actions\UpdateOsService;
use Liberu\ControlPanel\OsAdapters\Models\OsService;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsServiceResource;

final class EditOsService extends EditRecord
{
    protected static string $resource = OsServiceResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var OsService $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()->current_team_id, 404);

        return app(UpdateOsService::class)->execute($record, $data);
    }
}
