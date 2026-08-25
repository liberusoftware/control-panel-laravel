<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\HostingLogResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\HostingLogResource;

final class EditHostingLog extends EditRecord
{
    protected static string $resource = HostingLogResource::class;
}
