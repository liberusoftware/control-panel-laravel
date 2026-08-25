<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\WebServerResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\WebServerResource;

final class CreateWebServer extends CreateRecord
{
    protected static string $resource = WebServerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
