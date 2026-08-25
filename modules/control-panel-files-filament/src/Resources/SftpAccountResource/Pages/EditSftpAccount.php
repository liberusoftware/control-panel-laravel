<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources\SftpAccountResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\FilesFilament\Resources\SftpAccountResource;

final class EditSftpAccount extends EditRecord
{
    protected static string $resource = SftpAccountResource::class;
}
