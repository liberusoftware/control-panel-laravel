<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources\FilePermissionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Files\Actions\GrantFilePermission;
use Liberu\ControlPanel\FilesFilament\Resources\FilePermissionResource;

final class CreateFilePermission extends CreateRecord
{
    protected static string $resource = FilePermissionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        $data['team_id'] = auth()->user()?->current_team_id;

        return app(GrantFilePermission::class)->execute($data);
    }
}
