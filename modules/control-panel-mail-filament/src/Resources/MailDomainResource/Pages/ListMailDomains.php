<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\MailDomainResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MailFilament\Resources\MailDomainResource;

final class ListMailDomains extends ListRecords
{
    protected static string $resource = MailDomainResource::class;
}
