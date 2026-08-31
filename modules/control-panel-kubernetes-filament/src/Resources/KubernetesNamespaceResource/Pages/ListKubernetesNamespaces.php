<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNamespaceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNamespaceResource;

final class ListKubernetesNamespaces extends ListRecords
{
    protected static string $resource = KubernetesNamespaceResource::class;
}
