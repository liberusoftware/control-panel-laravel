<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationTemplateResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationTemplateResource;

final class EditAutomationTemplate extends EditRecord
{
    protected static string $resource = AutomationTemplateResource::class;
}
