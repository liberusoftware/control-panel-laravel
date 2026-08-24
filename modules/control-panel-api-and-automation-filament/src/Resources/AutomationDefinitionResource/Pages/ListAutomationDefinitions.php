<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationDefinitionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationDefinitionResource;

final class ListAutomationDefinitions extends ListRecords
{
    protected static string $resource = AutomationDefinitionResource::class;
}
