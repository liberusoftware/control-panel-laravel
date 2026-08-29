<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ApiAutomation\Models\WebhookEndpoint;

final class UpdateWebhook
{
    /** @param array<string, mixed> $attributes */
    public function execute(WebhookEndpoint $webhook, array $attributes): WebhookEndpoint
    {
        $name = trim((string) ($attributes['name'] ?? $webhook->name));
        $url = trim((string) ($attributes['url'] ?? $webhook->url));
        $events = $attributes['events'] ?? $webhook->events ?? [];
        $retryLimit = (int) ($attributes['retry_limit'] ?? $webhook->retry_limit);

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'A webhook name is required.']);
        }

        if (! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            throw ValidationException::withMessages(['url' => 'A valid HTTPS webhook URL is required.']);
        }

        if (! is_array($events) || $retryLimit < 0 || $retryLimit > 20) {
            throw ValidationException::withMessages(['webhook' => 'Webhook events and retry limit are invalid.']);
        }

        $webhook->forceFill([
            'name' => $name,
            'url' => $url,
            'events' => array_values(array_unique(array_map(static fn (mixed $event): string => trim((string) $event), $events))),
            'retry_limit' => $retryLimit,
        ])->save();

        return $webhook->refresh();
    }
}
