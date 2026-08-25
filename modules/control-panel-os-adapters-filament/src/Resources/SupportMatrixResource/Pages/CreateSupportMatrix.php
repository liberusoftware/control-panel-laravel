<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources\SupportMatrixResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\OsAdapters\Actions\RecordSupportMatrix;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\SupportMatrixResource;

final class CreateSupportMatrix extends CreateRecord
{
    protected static string $resource = SupportMatrixResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(RecordSupportMatrix::class)->execute($data);
    }
}
