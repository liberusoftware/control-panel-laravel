<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Enums\DomainStatus;
use Liberu\ControlPanel\WebHosting\Models\Domain;

final class SuspendDomain
{
    public function execute(Domain $domain, string $reason): Domain
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'A suspension reason is required.']);
        }

        if ($domain->status === DomainStatus::Archived) {
            throw ValidationException::withMessages(['domain' => 'An archived domain cannot be suspended.']);
        }

        return DB::transaction(function () use ($domain, $reason): Domain {
            $metadata = $domain->metadata ?? [];
            $metadata['suspension_reason'] = $reason;
            $metadata['suspended_at'] = now()->toIso8601String();
            $domain->forceFill(['status' => DomainStatus::Suspended, 'metadata' => $metadata])->save();

            return $domain->refresh();
        });
    }
}
