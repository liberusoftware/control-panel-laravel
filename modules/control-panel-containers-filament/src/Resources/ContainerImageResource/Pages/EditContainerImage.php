<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources\ContainerImageResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerImageResource;

final class EditContainerImage extends EditRecord
{
    protected static string $resource = ContainerImageResource::class;
}
