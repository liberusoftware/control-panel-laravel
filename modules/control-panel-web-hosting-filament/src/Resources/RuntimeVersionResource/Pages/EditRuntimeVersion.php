<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\RuntimeVersionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\RuntimeVersionResource;

final class EditRuntimeVersion extends EditRecord
{
    protected static string $resource = RuntimeVersionResource::class;
}
