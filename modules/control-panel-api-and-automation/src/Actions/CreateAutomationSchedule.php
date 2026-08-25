<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ApiAutomation\Enums\AutomationStatus;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationSchedule;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationTemplate;

final class CreateAutomationSchedule
{
    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): AutomationSchedule
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $cron = trim((string) ($attributes['cron'] ?? ''));
        $template = AutomationTemplate::query()->whereKey($attributes['template_id'] ?? null)->where('team_id', $attributes['team_id'] ?? null)->first();

        if ($name === '' || $cron === '' || $template === null) {
            throw ValidationException::withMessages(['schedule' => 'A name, cron expression, and same-team template are required.']);
        }

        return AutomationSchedule::query()->create([
            'id' => (string) Str::uuid(),
            'team_id' => $attributes['team_id'] ?? null,
            'name' => $name,
            'cron' => $cron,
            'timezone' => $attributes['timezone'] ?? 'UTC',
            'template_id' => $template->getKey(),
            'status' => AutomationStatus::Active,
            'next_run_at' => $attributes['next_run_at'] ?? null,
        ]);
    }
}
