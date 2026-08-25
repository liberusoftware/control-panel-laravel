<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\RuntimeVersionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\RuntimeVersionResource;

final class CreateRuntimeVersion extends CreateRecord
{
    protected static string $resource = RuntimeVersionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
