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
        return app(RotateDkimKey::class)->execute(
            (string) auth()->user()?->current_team_id,
            (string) $data['domain'],
            (string) ($data['selector'] ?? 'default'),
        );
    }
}
