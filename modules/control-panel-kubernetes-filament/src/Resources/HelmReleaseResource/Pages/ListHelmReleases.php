<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\HelmReleaseResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\KubernetesFilament\Resources\HelmReleaseResource;

final class ListHelmReleases extends ListRecords
{
    protected static string $resource = HelmReleaseResource::class;
}
