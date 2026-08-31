<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Actions;

use Liberu\ControlPanel\OsAdapters\Models\FirewallRule;

final class DeleteFirewallRule
{
    public function execute(FirewallRule $rule): void
    {
        $rule->delete();
    }
}
