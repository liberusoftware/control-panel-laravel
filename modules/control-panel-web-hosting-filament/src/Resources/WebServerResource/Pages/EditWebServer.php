<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\WebServerResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\WebServerResource;

final class EditWebServer extends EditRecord
{
    protected static string $resource = WebServerResource::class;
}
