<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources\OsAdapterResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsAdapterResource;

final class CreateOsAdapter extends CreateRecord
{
    protected static string $resource = OsAdapterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
