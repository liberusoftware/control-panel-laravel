<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsFilament\Resources\AccountDelegationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\AccountsFilament\Resources\AccountDelegationResource;

final class EditAccountDelegation extends EditRecord
{
    protected static string $resource = AccountDelegationResource::class;
}
