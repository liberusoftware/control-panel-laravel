<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationDefinitionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationDefinitionResource;

final class EditAutomationDefinition extends EditRecord
{
    protected static string $resource = AutomationDefinitionResource::class;
}
