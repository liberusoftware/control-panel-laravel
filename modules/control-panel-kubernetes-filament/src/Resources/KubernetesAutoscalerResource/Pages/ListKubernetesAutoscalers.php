<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesAutoscalerResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesAutoscalerResource;

final class ListKubernetesAutoscalers extends ListRecords
{
    protected static string $resource = KubernetesAutoscalerResource::class;
}
