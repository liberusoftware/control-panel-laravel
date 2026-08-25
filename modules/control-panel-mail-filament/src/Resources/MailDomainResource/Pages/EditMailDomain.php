<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\MailDomainResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\MailFilament\Resources\MailDomainResource;

final class EditMailDomain extends EditRecord
{
    protected static string $resource = MailDomainResource::class;
}
