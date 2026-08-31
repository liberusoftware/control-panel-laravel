<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\RedirectResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\WebHosting\Actions\CreateRedirect as CreateRedirectAction;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHostingFilament\Resources\RedirectResource;

final class CreateRedirect extends CreateRecord
{
    protected static string $resource = RedirectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        abort_if(! isset($data['domain_id']), 422, 'A domain is required.');
        $domain = Domain::query()->whereKey($data['domain_id'])->where('team_id', auth()->user()?->current_team_id)->firstOrFail();

        return app(CreateRedirectAction::class)->execute($domain, $data);
    }
}
