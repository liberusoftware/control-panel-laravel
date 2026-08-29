<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources\ZoneResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Dns\Actions\UpdateZone;
use Liberu\ControlPanel\Dns\Models\Zone;
use Liberu\ControlPanel\DnsFilament\Resources\ZoneResource;

final class EditZone extends EditRecord
{
    protected static string $resource = ZoneResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Zone $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateZone::class)->execute($record, $data);
    }
}
