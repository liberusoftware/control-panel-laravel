<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Actions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\OsAdapters\Models\FirewallRule;

final class CreateFirewallRule
{
    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): FirewallRule
    {
        Validator::make($attributes, [
            'team_id' => ['required'],
            'node_id' => ['required', 'string'],
            'direction' => ['required', 'in:inbound,outbound'],
            'action' => ['required', 'in:allow,deny,reject'],
            'protocol' => ['nullable', 'string', 'max:20'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'source' => ['nullable', 'string', 'max:64'],
            'comment' => ['nullable', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
        ])->validate();
        $this->validateSource($attributes['source'] ?? null);

        return FirewallRule::query()->create($attributes);
    }

    public function validateSource(mixed $source): void
    {
        if ($source === null || $source === '') {
            return;
        }

        $valid = filter_var($source, FILTER_VALIDATE_IP) !== false;
        if (! $valid && str_contains((string) $source, '/')) {
            [$address, $prefix] = array_pad(explode('/', (string) $source, 2), 2, null);
            $valid = filter_var($address, FILTER_VALIDATE_IP) !== false
                && is_numeric($prefix)
                && (int) $prefix >= 0
                && (str_contains($address, ':') ? (int) $prefix <= 128 : (int) $prefix <= 32);
        }

        if (! $valid) {
            throw ValidationException::withMessages(['source' => 'The source must be a valid IP address or CIDR range.']);
        }
    }
}
