<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\MailRouteResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\MailFilament\Resources\MailRouteResource;

final class EditMailRoute extends EditRecord
{
    protected static string $resource = MailRouteResource::class;
}
