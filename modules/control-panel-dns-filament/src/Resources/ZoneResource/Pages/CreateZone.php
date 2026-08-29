<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources\ZoneResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\DnsFilament\Resources\ZoneResource;

final class CreateZone extends CreateRecord
{
    protected static string $resource = ZoneResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
