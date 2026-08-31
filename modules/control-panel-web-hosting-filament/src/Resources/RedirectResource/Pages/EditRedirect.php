<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\RedirectResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\WebHosting\Actions\UpdateRedirect;
use Liberu\ControlPanel\WebHosting\Models\Redirect;
use Liberu\ControlPanel\WebHostingFilament\Resources\RedirectResource;

final class EditRedirect extends EditRecord
{
    protected static string $resource = RedirectResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Redirect $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateRedirect::class)->execute($record, $data);
    }
}
