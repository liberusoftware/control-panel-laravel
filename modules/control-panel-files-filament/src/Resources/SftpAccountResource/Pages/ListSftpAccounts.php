<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources\SftpAccountResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\FilesFilament\Resources\SftpAccountResource;

final class ListSftpAccounts extends ListRecords
{
    protected static string $resource = SftpAccountResource::class;
}
