<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources\SecurityFindingResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Security\Actions\UpdateSecurityFinding;
use Liberu\ControlPanel\SecurityFilament\Resources\SecurityFindingResource;

final class EditSecurityFinding extends EditRecord
{
    protected static string $resource = SecurityFindingResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateSecurityFinding::class)->execute($record, $data);
    }
}
