<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\VirtualHostResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\WebHosting\Actions\UpdateVirtualHost;
use Liberu\ControlPanel\WebHosting\Models\VirtualHost;
use Liberu\ControlPanel\WebHostingFilament\Resources\VirtualHostResource;

final class EditVirtualHost extends EditRecord
{
    protected static string $resource = VirtualHostResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var VirtualHost $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->domain?->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateVirtualHost::class)->execute($record, $data);
    }
}
