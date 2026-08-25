<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources\FileQuotaResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\FilesFilament\Resources\FileQuotaResource;

final class EditFileQuota extends EditRecord
{
    protected static string $resource = FileQuotaResource::class;
}
