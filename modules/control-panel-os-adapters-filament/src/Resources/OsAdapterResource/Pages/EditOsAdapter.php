<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources\OsAdapterResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsAdapterResource;

final class EditOsAdapter extends EditRecord
{
    protected static string $resource = OsAdapterResource::class;
}
