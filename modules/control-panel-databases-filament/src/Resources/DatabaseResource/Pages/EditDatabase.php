<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseResource;

final class EditDatabase extends EditRecord
{
    protected static string $resource = DatabaseResource::class;
}
