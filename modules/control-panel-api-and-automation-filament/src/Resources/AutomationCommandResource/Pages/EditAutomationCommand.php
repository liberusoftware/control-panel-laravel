<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationCommandResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationCommandResource;

final class EditAutomationCommand extends EditRecord
{
    protected static string $resource = AutomationCommandResource::class;
}
