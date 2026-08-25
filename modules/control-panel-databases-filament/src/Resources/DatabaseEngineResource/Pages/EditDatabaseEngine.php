<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseEngineResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseEngineResource;

final class EditDatabaseEngine extends EditRecord
{
    protected static string $resource = DatabaseEngineResource::class;
}
