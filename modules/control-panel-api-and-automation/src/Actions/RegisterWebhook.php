<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ApiAutomation\Models\WebhookEndpoint;

final class RegisterWebhook
{
    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): WebhookEndpoint
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $url = trim((string) ($attributes['url'] ?? ''));
        if ($name === '' || ! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['https'], true)) {
            throw ValidationException::withMessages(['url' => 'A valid HTTPS webhook URL is required.']);
        }
        return WebhookEndpoint::query()->create(['id' => (string) Str::uuid(), 'team_id' => $attributes['team_id'] ?? null, 'name' => $name, 'url' => $url, 'events' => array_values(array_unique($attributes['events'] ?? [])), 'secret' => $attributes['secret'] ?? Str::random(48), 'status' => 'active', 'retry_limit' => min(max((int) ($attributes['retry_limit'] ?? 5), 0), 20)]);
    }
}
