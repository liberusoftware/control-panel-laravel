<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsFilament\Resources\AccountResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\AccountsFilament\Resources\AccountResource;

final class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;
}
