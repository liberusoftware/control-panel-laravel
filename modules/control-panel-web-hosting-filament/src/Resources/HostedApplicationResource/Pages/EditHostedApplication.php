<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\HostedApplicationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\WebHosting\Actions\UpdateHostedApplication;
use Liberu\ControlPanel\WebHosting\Models\HostedApplication;
use Liberu\ControlPanel\WebHostingFilament\Resources\HostedApplicationResource;

final class EditHostedApplication extends EditRecord
{
    protected static string $resource = HostedApplicationResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var HostedApplication $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateHostedApplication::class)->execute($record, $data);
    }
}
