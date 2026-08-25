<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\HostedApplication;
use Liberu\ControlPanel\WebHosting\Models\HostingLog;
use Liberu\ControlPanel\WebHosting\Models\RuntimeVersion;
use Liberu\ControlPanel\WebHosting\Models\WebServer;

final class RegisterHostingResource
{
    public function execute(array $attributes): Model
    {
        $kind = (string) ($attributes['kind'] ?? '');
        $map = [
            'runtime' => RuntimeVersion::class,
            'server' => WebServer::class,
            'log' => HostingLog::class,
            'application' => HostedApplication::class,
        ];
        if (! isset($map[$kind])) {
            throw ValidationException::withMessages(['kind' => 'Unsupported hosting resource.']);
        }

        $attributes['id'] = $attributes['id'] ?? (string) Str::uuid();
        $attributes['team_id'] = $attributes['team_id'] ?? null;

        if ($kind === 'application') {
            $domainId = $attributes['domain_id'] ?? null;
            $domainExists = $domainId !== null
                && Domain::query()->whereKey($domainId)->where('team_id', $attributes['team_id'])->exists();

            if (! $domainExists) {
                throw ValidationException::withMessages(['domain_id' => 'The domain must belong to the current team.']);
            }
        }

        if ($kind === 'log') {
            $attributes['occurred_at'] = $attributes['occurred_at'] ?? now();
            $attributes['kind'] = $attributes['log_kind'] ?? 'access';
        }

        if ($kind === 'server') {
            $attributes['status'] = $attributes['status'] ?? 'active';
        }

        if ($kind === 'application') {
            $attributes['status'] = $attributes['status'] ?? 'pending';
        }

        if ($kind !== 'log') {
            unset($attributes['kind']);
        }

        unset($attributes['log_kind']);

        return $map[$kind]::query()->create($attributes);
    }
}
