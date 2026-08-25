<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\ApiCredentialResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\ApiCredentialResource;

final class ListApiCredentials extends ListRecords
{
    protected static string $resource = ApiCredentialResource::class;
}
