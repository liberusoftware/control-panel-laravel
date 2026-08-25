<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationScheduleResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationScheduleResource;

final class ListAutomationSchedules extends ListRecords
{
    protected static string $resource = AutomationScheduleResource::class;
}
