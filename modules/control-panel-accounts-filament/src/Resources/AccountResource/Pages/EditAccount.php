<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsFilament\Resources\AccountResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\AccountsFilament\Resources\AccountResource;

final class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;
}
