<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\MailAliasResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\MailFilament\Resources\MailAliasResource;

final class EditMailAlias extends EditRecord
{
    protected static string $resource = MailAliasResource::class;
}
