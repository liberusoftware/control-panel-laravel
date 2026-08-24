<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources\BackupPolicyResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupPolicyResource;

final class ListBackupPolicies extends ListRecords
{
    protected static string $resource = BackupPolicyResource::class;
}
