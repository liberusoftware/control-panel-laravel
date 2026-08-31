<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\HelmReleaseResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Kubernetes\Actions\RegisterKubernetesAsset;
use Liberu\ControlPanel\KubernetesFilament\Resources\HelmReleaseResource;

final class CreateHelmRelease extends CreateRecord
{
    protected static string $resource = HelmReleaseResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return app(RegisterKubernetesAsset::class)->execute(array_merge($data, ['team_id' => $teamId, 'kind' => 'helm']));
    }
}
