<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\PhpConfigurationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\PhpConfigurationResource;

final class EditPhpConfiguration extends EditRecord
{
    protected static string $resource = PhpConfigurationResource::class;
}
