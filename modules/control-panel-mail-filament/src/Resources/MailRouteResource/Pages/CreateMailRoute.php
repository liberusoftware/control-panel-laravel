<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\MailRouteResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\Mail\Actions\CreateMailRoute as CreateMailRouteAction;
use Liberu\ControlPanel\MailFilament\Resources\MailRouteResource;

final class CreateMailRoute extends CreateRecord
{
    protected static string $resource = MailRouteResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return app(CreateMailRouteAction::class)->execute($data);
    }
}
