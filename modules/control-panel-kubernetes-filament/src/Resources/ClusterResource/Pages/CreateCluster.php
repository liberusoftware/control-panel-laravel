<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\ClusterResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\KubernetesFilament\Resources\ClusterResource;

final class CreateCluster extends CreateRecord
{
    protected static string $resource = ClusterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
