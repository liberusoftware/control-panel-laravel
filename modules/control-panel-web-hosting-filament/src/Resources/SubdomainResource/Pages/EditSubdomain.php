<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\SubdomainResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\WebHosting\Actions\UpdateSubdomain as UpdateSubdomainAction;
use Liberu\ControlPanel\WebHostingFilament\Resources\SubdomainResource;

final class EditSubdomain extends EditRecord
{
    protected static string $resource = SubdomainResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        unset($data['domain_id'], $data['subdomain']);

        return app(UpdateSubdomainAction::class)->execute($record, $data);
    }
}
