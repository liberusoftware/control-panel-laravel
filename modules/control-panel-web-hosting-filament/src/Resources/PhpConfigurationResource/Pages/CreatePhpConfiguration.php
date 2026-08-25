<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\PhpConfigurationResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\PhpConfigurationResource;

final class CreatePhpConfiguration extends CreateRecord
{
    protected static string $resource = PhpConfigurationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
