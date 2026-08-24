<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\MailAccountResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MailFilament\Resources\MailAccountResource;

final class ListMailAccounts extends ListRecords
{
    protected static string $resource = MailAccountResource::class;
}
