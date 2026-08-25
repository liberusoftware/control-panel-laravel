<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource;

final class ListKubernetesNodes extends ListRecords
{
    protected static string $resource = KubernetesNodeResource::class;
}
