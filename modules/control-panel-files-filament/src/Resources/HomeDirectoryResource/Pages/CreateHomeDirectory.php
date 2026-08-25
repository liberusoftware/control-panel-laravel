<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources\HomeDirectoryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Files\Actions\CreateHomeDirectory as CreateHomeDirectoryAction;
use Liberu\ControlPanel\FilesFilament\Resources\HomeDirectoryResource;

final class CreateHomeDirectory extends CreateRecord
{
    protected static string $resource = HomeDirectoryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return app(CreateHomeDirectoryAction::class)->execute($data);
    }
}
