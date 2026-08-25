<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationScheduleResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationScheduleResource;

final class EditAutomationSchedule extends EditRecord
{
    protected static string $resource = AutomationScheduleResource::class;
}
