<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources\OsPackageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsPackageResource;

final class CreateOsPackage extends CreateRecord
{
    protected static string $resource = OsPackageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
