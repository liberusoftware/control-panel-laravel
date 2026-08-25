<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\RedirectResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\RedirectResource;

final class CreateRedirect extends CreateRecord
{
    protected static string $resource = RedirectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
