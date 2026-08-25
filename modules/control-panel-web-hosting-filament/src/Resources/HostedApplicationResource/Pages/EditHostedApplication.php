<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\HostedApplicationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\HostedApplicationResource;

final class EditHostedApplication extends EditRecord
{
    protected static string $resource = HostedApplicationResource::class;
}
