<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsFilament\Resources\AccountDelegationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Accounts\Actions\UpdateDelegation;
use Liberu\ControlPanel\Accounts\Models\AccountDelegation;
use Liberu\ControlPanel\AccountsFilament\Resources\AccountDelegationResource;

final class EditAccountDelegation extends EditRecord
{
    protected static string $resource = AccountDelegationResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var AccountDelegation $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateDelegation::class)->execute($record->loadMissing('account'), $data);
    }
}
