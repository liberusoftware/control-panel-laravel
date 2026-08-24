<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Enums\DomainStatus;
use Liberu\ControlPanel\WebHosting\Events\DomainCreated;
use Liberu\ControlPanel\WebHosting\Models\Domain;

final readonly class CreateDomain
{
    public function __construct(private Dispatcher $events) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): Domain
    {
        $hostname = mb_strtolower(trim((string) ($attributes['hostname'] ?? '')));
        if (! filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw ValidationException::withMessages(['hostname' => 'Enter a valid domain hostname.']);
        }

        return DB::transaction(function () use ($attributes, $hostname): Domain {
            $domain = Domain::query()->create([
                'id' => (string) Str::uuid(),
                'team_id' => $attributes['team_id'] ?? null,
                'account_id' => $attributes['account_id'] ?? null,
                'hostname' => rtrim($hostname, '.'),
                'status' => DomainStatus::Pending,
                'metadata' => $attributes['metadata'] ?? [],
            ]);
            $this->events->dispatch(new DomainCreated((string) $domain->getKey(), $domain->hostname));

            return $domain;
        });
    }
}
