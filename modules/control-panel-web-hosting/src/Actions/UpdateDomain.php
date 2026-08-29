<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\Domain;

final class UpdateDomain
{
    /** @param array<string, mixed> $attributes */
    public function execute(Domain $domain, array $attributes): Domain
    {
        $hostname = mb_strtolower(trim((string) ($attributes['hostname'] ?? $domain->hostname)));
        if (! filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw ValidationException::withMessages(['hostname' => 'Enter a valid domain hostname.']);
        }

        return DB::transaction(function () use ($domain, $attributes, $hostname): Domain {
            $domain->forceFill([
                'hostname' => rtrim($hostname, '.'),
                'account_id' => $attributes['account_id'] ?? $domain->account_id,
                'metadata' => $attributes['metadata'] ?? $domain->metadata,
            ])->save();

            return $domain->refresh();
        });
    }
}
