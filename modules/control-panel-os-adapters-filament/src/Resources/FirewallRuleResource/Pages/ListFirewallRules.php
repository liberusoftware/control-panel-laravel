<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources\FirewallRuleResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\FirewallRuleResource;

final class ListFirewallRules extends ListRecords
{
    protected static string $resource = FirewallRuleResource::class;
}
