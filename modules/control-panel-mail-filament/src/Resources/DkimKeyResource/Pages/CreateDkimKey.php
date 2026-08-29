<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\DkimKeyResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Mail\Actions\RotateDkimKey;
use Liberu\ControlPanel\MailFilament\Resources\DkimKeyResource;

final class CreateDkimKey extends CreateRecord
{
    protected static string $resource = DkimKeyResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return app(RotateDkimKey::class)->execute(
            (string) $teamId,
            (string) $data['domain'],
            (string) ($data['selector'] ?? 'default'),
        );
    }
}
