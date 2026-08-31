<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesIngressResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesIngressResource;

final class ListKubernetesIngresses extends ListRecords
{
    protected static string $resource = KubernetesIngressResource::class;
}
