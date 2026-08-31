<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\HelmReleaseResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Kubernetes\Actions\UpdateHelmRelease;
use Liberu\ControlPanel\KubernetesFilament\Resources\HelmReleaseResource;

final class EditHelmRelease extends EditRecord
{
    protected static string $resource = HelmReleaseResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateHelmRelease::class)->execute($record, $data);
    }
}
