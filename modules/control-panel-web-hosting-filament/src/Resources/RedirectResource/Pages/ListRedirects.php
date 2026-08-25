<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\RedirectResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\WebHostingFilament\Resources\RedirectResource;

final class ListRedirects extends ListRecords
{
    protected static string $resource = RedirectResource::class;
}
