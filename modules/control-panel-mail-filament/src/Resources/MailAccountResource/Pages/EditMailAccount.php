<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\MailAccountResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\MailFilament\Resources\MailAccountResource;

final class EditMailAccount extends EditRecord
{
    protected static string $resource = MailAccountResource::class;
}
