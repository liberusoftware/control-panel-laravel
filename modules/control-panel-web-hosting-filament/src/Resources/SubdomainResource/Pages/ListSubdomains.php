<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\SubdomainResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\WebHostingFilament\Resources\SubdomainResource;

final class ListSubdomains extends ListRecords
{
    protected static string $resource = SubdomainResource::class;
}
