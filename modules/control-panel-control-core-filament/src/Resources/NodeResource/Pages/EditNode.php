<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreFilament\Resources\NodeResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\ControlCoreFilament\Resources\NodeResource;

final class EditNode extends EditRecord
{
    protected static string $resource = NodeResource::class;
}
