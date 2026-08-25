<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources\DnsCheckResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\DnsFilament\Resources\DnsCheckResource;

final class CreateDnsCheck extends CreateRecord
{
    protected static string $resource = DnsCheckResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
