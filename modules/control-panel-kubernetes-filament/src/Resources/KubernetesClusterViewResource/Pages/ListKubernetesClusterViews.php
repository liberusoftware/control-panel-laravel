<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesClusterViewResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesClusterViewResource;

final class ListKubernetesClusterViews extends ListRecords
{
    protected static string $resource = KubernetesClusterViewResource::class;
}
