<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources\AlertRuleResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MonitoringFilament\Resources\AlertRuleResource;

final class ListAlertRules extends ListRecords
{
    protected static string $resource = AlertRuleResource::class;
}
