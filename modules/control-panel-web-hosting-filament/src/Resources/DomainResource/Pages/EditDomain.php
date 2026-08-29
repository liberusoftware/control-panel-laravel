<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\DomainResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\WebHosting\Actions\UpdateDomain;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHostingFilament\Resources\DomainResource;

final class EditDomain extends EditRecord
{
    protected static string $resource = DomainResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Domain $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateDomain::class)->execute($record, $data);
    }
}
