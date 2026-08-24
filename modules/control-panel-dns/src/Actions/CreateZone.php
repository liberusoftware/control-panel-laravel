<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Enums\ZoneStatus;
use Liberu\ControlPanel\Dns\Events\ZoneCreated;
use Liberu\ControlPanel\Dns\Models\Zone;

final readonly class CreateZone
{
    public function __construct(private Dispatcher $events) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): Zone
    {
        $domain = strtolower(trim((string) ($attributes['domain'] ?? '')));
        if ($domain === '' || filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw ValidationException::withMessages(['domain' => 'A valid DNS domain is required.']);
        }

        return DB::transaction(function () use ($attributes, $domain): Zone {
            $zone = Zone::query()->create(['id' => (string) Str::uuid(), 'team_id' => $attributes['team_id'] ?? null, 'domain' => $domain, 'status' => ZoneStatus::Draft, 'provider' => $attributes['provider'] ?? null, 'dnssec_enabled' => (bool) ($attributes['dnssec_enabled'] ?? false), 'metadata' => $attributes['metadata'] ?? []]);
            $this->events->dispatch(new ZoneCreated($zone));

            return $zone;
        });
    }
}
