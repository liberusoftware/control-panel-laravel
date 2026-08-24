<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\ClusterResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\KubernetesFilament\Resources\ClusterResource;

final class ListClusters extends ListRecords
{
    protected static string $resource = ClusterResource::class;
}
