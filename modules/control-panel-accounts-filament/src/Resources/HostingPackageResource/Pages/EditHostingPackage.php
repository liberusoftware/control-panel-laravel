<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsFilament\Resources\HostingPackageResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Accounts\Actions\UpdateHostingPackage;
use Liberu\ControlPanel\Accounts\Models\HostingPackage;
use Liberu\ControlPanel\AccountsFilament\Resources\HostingPackageResource;

final class EditHostingPackage extends EditRecord
{
    protected static string $resource = HostingPackageResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var HostingPackage $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateHostingPackage::class)->execute($record, $data);
    }
}
