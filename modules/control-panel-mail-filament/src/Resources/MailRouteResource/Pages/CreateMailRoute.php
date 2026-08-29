<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\MailRouteResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Mail\Actions\CreateMailRoute as CreateMailRouteAction;
use Liberu\ControlPanel\MailFilament\Resources\MailRouteResource;

final class CreateMailRoute extends CreateRecord
{
    protected static string $resource = MailRouteResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        $data['team_id'] = auth()->user()?->current_team_id;

        return app(CreateMailRouteAction::class)->execute($data);
    }
}
