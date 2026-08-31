<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources\OffsiteTransferResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\BackupsFilament\Resources\OffsiteTransferResource;

final class ListOffsiteTransfers extends ListRecords
{
    protected static string $resource = OffsiteTransferResource::class;
}
