<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\SslCertificateResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\SslCertificateResource;

final class CreateSslCertificate extends CreateRecord
{
    protected static string $resource = SslCertificateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
