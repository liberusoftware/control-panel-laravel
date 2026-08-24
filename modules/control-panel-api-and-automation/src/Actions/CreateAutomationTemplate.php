<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationTemplate;

final class CreateAutomationTemplate
{
    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): AutomationTemplate
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $version = trim((string) ($attributes['version'] ?? ''));
        $steps = $attributes['steps'] ?? null;

        if ($name === '' || $version === '' || ! is_array($steps) || $steps === []) {
            throw ValidationException::withMessages(['template' => 'A template name, version, and at least one step are required.']);
        }

        return AutomationTemplate::query()->create([
            'id' => (string) Str::uuid(),
            'team_id' => $attributes['team_id'] ?? null,
            'name' => $name,
            'version' => $version,
            'description' => $attributes['description'] ?? null,
            'inputs' => $attributes['inputs'] ?? [],
            'steps' => array_values($steps),
            'active' => $attributes['active'] ?? true,
        ]);
    }
}
