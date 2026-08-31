<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesRbacBindingResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesRbacBindingResource;

final class ListKubernetesRbacBindings extends ListRecords
{
    protected static string $resource = KubernetesRbacBindingResource::class;
}
