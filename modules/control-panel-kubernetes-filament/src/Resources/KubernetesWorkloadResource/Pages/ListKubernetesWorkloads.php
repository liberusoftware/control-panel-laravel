<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesWorkloadResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesWorkloadResource;

final class ListKubernetesWorkloads extends ListRecords
{
    protected static string $resource = KubernetesWorkloadResource::class;
}
