<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\ClusterResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\KubernetesFilament\Resources\ClusterResource;

final class EditCluster extends EditRecord
{
    protected static string $resource = ClusterResource::class;
}
