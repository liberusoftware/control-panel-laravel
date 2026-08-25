<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources\BackupPolicyResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupPolicyResource;

final class EditBackupPolicy extends EditRecord
{
    protected static string $resource = BackupPolicyResource::class;
}
