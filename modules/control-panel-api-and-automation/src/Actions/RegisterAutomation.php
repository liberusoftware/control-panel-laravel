<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Actions;

use Liberu\ControlPanel\ApiAutomation\Models\AutomationDefinition;

final class RegisterAutomation
{
    public function execute(array $attributes): AutomationDefinition
    {
        return AutomationDefinition::query()->create(array_merge(['status' => 'draft', 'definition' => [], 'credentials' => []], $attributes));
    }
}
