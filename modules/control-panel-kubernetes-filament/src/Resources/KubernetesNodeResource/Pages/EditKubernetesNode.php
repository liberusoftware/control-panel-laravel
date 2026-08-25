<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource;

final class EditKubernetesNode extends EditRecord
{
    protected static string $resource = KubernetesNodeResource::class;
}
