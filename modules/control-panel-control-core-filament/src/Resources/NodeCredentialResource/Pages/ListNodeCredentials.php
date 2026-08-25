<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreFilament\Resources\NodeCredentialResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ControlCoreFilament\Resources\NodeCredentialResource;

final class ListNodeCredentials extends ListRecords
{
    protected static string $resource = NodeCredentialResource::class;
}
