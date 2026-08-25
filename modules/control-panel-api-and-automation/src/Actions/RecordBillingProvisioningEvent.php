<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ApiAutomation\Models\BillingProvisioningEvent;

final class RecordBillingProvisioningEvent
{
    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): BillingProvisioningEvent
    {
        $externalId = trim((string) ($attributes['external_id'] ?? ''));
        $eventType = trim((string) ($attributes['event_type'] ?? ''));
        $payload = $attributes['payload'] ?? null;

        if ($externalId === '' || $eventType === '' || ! is_array($payload)) {
            throw ValidationException::withMessages(['event' => 'An external id, event type, and object payload are required.']);
        }

        return BillingProvisioningEvent::query()->firstOrCreate(
            ['team_id' => $attributes['team_id'] ?? null, 'external_id' => $externalId],
            ['id' => (string) Str::uuid(), 'event_type' => $eventType, 'payload' => $payload, 'status' => 'pending'],
        );
    }
}
