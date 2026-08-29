<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources\PropagationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\DnsFilament\Resources\PropagationResource;

final class ListPropagationChecks extends ListRecords
{
    protected static string $resource = PropagationResource::class;
}
