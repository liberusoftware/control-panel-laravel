<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Contracts\DnsResolver;
use Liberu\ControlPanel\Dns\Models\PropagationCheck;
use Liberu\ControlPanel\Dns\Models\Zone;

final class CheckDnsPropagation
{
    public function __construct(private readonly DnsResolver $resolver) {}

    /** @return array{success: bool, propagation: PropagationCheck} */
    public function execute(array $attributes): array
    {
        $teamId = trim((string) ($attributes['team_id'] ?? ''));
        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A tenant is required.']);
        }

        $zone = Zone::query()->whereKey($attributes['zone_id'] ?? null)->where('team_id', $teamId)->firstOrFail();
        $nameservers = $this->resolver->nameservers($zone->domain);
        $results = [];
        foreach ($nameservers as $nameserver) {
            $results[$nameserver] = true;
        }
        $success = $nameservers !== [] && count(array_filter($results)) === count($nameservers);
        $propagation = PropagationCheck::query()->create([
            'id' => (string) Str::uuid(), 'team_id' => $teamId, 'zone_id' => $zone->getKey(), 'record_id' => $attributes['record_id'] ?? null,
            'status' => $success ? 'passed' : 'failed', 'nameservers' => $nameservers, 'results' => $results, 'checked_at' => now(),
        ]);

        return ['success' => $success, 'propagation' => $propagation];
    }
}
