<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament\Resources\CertificateResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Certificates\Actions\UpdateCertificate;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateResource;

final class EditCertificate extends EditRecord
{
    protected static string $resource = CertificateResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateCertificate::class)->execute($record, $data);
    }
}
