<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ApiAutomation\Enums\AutomationStatus;
use Liberu\ControlPanel\ApiAutomation\Exceptions\OrchestrationIdempotencyConflict;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationTemplate;
use Liberu\ControlPanel\ApiAutomation\Models\OrchestrationRun;

final class StartOrchestration
{
    /** @param array<string, mixed> $input */
    public function execute(AutomationTemplate $template, array $input, ?string $teamId, ?string $idempotencyKey = null): OrchestrationRun
    {
        if (! $template->active) {
            throw ValidationException::withMessages(['template' => 'The automation template is inactive.']);
        }
        if ($idempotencyKey !== null) {
            $existing = OrchestrationRun::query()->where('team_id', $teamId)->where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                if ((string) $existing->template_id !== (string) $template->getKey() || ($existing->input ?? []) !== $input) {
                    throw new OrchestrationIdempotencyConflict();
                }

                return $existing;
            }
        }

        return OrchestrationRun::query()->create(['id' => (string) Str::uuid(), 'team_id' => $teamId, 'template_id' => $template->getKey(), 'status' => AutomationStatus::Active, 'input' => $input, 'idempotency_key' => $idempotencyKey, 'started_at' => now()]);
    }
}
