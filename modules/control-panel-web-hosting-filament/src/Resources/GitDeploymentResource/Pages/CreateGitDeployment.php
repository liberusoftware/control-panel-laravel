<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\GitDeploymentResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\GitDeploymentResource;

final class CreateGitDeployment extends CreateRecord
{
    protected static string $resource = GitDeploymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
