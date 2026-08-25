<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsFilament\Resources\AccountDelegationResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Accounts\Actions\DelegateAccount;
use Liberu\ControlPanel\Accounts\Models\Account;
use Liberu\ControlPanel\AccountsFilament\Resources\AccountDelegationResource;

final class CreateAccountDelegation extends CreateRecord
{
    protected static string $resource = AccountDelegationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $account = Account::query()
            ->whereKey($data['account_id'] ?? null)
            ->where('team_id', auth()->user()?->current_team_id)
            ->firstOrFail();

        return app(DelegateAccount::class)->execute($account, $data);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
