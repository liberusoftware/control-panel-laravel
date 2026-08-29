<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\MailRouteResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MailFilament\Resources\MailRouteResource;

final class ListMailRoutes extends ListRecords
{
    protected static string $resource = MailRouteResource::class;
}
