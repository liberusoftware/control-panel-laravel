<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesStorageClaimResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesStorageClaimResource;

final class ListKubernetesStorageClaims extends ListRecords
{
    protected static string $resource = KubernetesStorageClaimResource::class;
}
