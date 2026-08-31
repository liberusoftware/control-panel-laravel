<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources\BackupEncryptionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupEncryptionResource;

final class ListBackupEncryptions extends ListRecords
{
    protected static string $resource = BackupEncryptionResource::class;
}
