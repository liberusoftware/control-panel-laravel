<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationCommand;

final class RegisterAutomationCommand
{
    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): AutomationCommand
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $command = trim((string) ($attributes['command'] ?? ''));

        if ($name === '' || $command === '') {
            throw ValidationException::withMessages(['command' => 'A command name and executable command are required.']);
        }

        return AutomationCommand::query()->create([
            'id' => (string) Str::uuid(),
            'team_id' => $attributes['team_id'] ?? null,
            'name' => $name,
            'description' => $attributes['description'] ?? null,
            'command' => $command,
            'arguments' => $attributes['arguments'] ?? [],
            'enabled' => $attributes['enabled'] ?? true,
        ]);
    }
}
