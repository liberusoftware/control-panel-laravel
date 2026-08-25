<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource;

final class CreateKubernetesNode extends CreateRecord
{
    protected static string $resource = KubernetesNodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
