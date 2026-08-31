<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesUpgradeResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesUpgradeResource;

final class ListKubernetesUpgrades extends ListRecords
{
    protected static string $resource = KubernetesUpgradeResource::class;
}
