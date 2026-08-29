<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsFilament\Resources\AccountResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Accounts\Actions\UpdateAccount as UpdateAccountAction;
use Liberu\ControlPanel\Accounts\Models\Account;
use Liberu\ControlPanel\AccountsFilament\Resources\AccountResource;

final class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Account $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateAccountAction::class)->execute($record, $data);
    }
}
