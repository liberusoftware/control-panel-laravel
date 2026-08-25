<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationCommandResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationCommandResource;

final class ListAutomationCommands extends ListRecords
{
    protected static string $resource = AutomationCommandResource::class;
}
