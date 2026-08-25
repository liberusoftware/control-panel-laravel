<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\RedirectResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\RedirectResource;

final class EditRedirect extends EditRecord
{
    protected static string $resource = RedirectResource::class;
}
