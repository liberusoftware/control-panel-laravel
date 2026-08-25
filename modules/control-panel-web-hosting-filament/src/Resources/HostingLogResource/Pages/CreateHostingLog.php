<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\HostingLogResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\HostingLogResource;

final class CreateHostingLog extends CreateRecord
{
    protected static string $resource = HostingLogResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
