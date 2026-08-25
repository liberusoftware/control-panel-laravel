<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\DkimKeyResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MailFilament\Resources\DkimKeyResource;

final class ListDkimKeys extends ListRecords
{
    protected static string $resource = DkimKeyResource::class;
}
