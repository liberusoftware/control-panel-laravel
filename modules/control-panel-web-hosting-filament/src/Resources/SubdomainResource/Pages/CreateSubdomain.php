<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\SubdomainResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\WebHosting\Actions\CreateSubdomain as CreateSubdomainAction;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHostingFilament\Resources\SubdomainResource;

final class CreateSubdomain extends CreateRecord
{
    protected static string $resource = SubdomainResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $domain = Domain::query()->where('team_id', $teamId)->findOrFail($data['domain_id']);
        unset($data['domain_id']);

        return app(CreateSubdomainAction::class)->execute($domain, $data);
    }
}
