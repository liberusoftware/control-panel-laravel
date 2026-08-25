<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources\SecurityFindingResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\SecurityFilament\Resources\SecurityFindingResource;

final class EditSecurityFinding extends EditRecord
{
    protected static string $resource = SecurityFindingResource::class;
}
