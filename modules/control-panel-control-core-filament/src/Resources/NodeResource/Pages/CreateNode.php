<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreFilament\Resources\NodeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNode;
use Liberu\ControlPanel\ControlCoreFilament\Resources\NodeResource;

final class CreateNode extends CreateRecord
{
    protected static string $resource = NodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(RegisterNode::class)->execute($data);
    }
}
