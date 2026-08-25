<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\DomainResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\DomainResource;

final class EditDomain extends EditRecord
{
    protected static string $resource = DomainResource::class;
}
