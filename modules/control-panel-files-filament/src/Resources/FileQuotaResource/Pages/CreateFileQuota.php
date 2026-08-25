<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources\FileQuotaResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\FilesFilament\Resources\FileQuotaResource;

final class CreateFileQuota extends CreateRecord
{
    protected static string $resource = FileQuotaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
