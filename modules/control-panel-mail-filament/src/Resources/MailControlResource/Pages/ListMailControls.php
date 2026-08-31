<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\MailControlResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MailFilament\Resources\MailControlResource;

final class ListMailControls extends ListRecords
{
    protected static string $resource = MailControlResource::class;
}
