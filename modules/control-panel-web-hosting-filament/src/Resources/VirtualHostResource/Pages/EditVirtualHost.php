<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\VirtualHostResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\VirtualHostResource;

final class EditVirtualHost extends EditRecord
{
    protected static string $resource = VirtualHostResource::class;
}
