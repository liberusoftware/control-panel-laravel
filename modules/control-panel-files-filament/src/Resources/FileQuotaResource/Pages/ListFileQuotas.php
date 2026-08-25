<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources\FileQuotaResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\FilesFilament\Resources\FileQuotaResource;

final class ListFileQuotas extends ListRecords
{
    protected static string $resource = FileQuotaResource::class;
}
