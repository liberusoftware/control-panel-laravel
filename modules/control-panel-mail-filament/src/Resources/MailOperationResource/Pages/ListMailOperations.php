<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\MailOperationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MailFilament\Resources\MailOperationResource;

final class ListMailOperations extends ListRecords
{
    protected static string $resource = MailOperationResource::class;
}
