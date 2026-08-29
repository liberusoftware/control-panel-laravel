<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources\BackupPolicyResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupPolicyResource;

final class CreateBackupPolicy extends CreateRecord
{
    protected static string $resource = BackupPolicyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
