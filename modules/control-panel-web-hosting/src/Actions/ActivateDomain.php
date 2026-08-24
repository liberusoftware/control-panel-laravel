<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Enums\DomainStatus;
use Liberu\ControlPanel\WebHosting\Models\Domain;

final class ActivateDomain
{
    public function execute(Domain $domain): Domain
    {
        if ($domain->status === DomainStatus::Archived) {
            throw ValidationException::withMessages(['status' => 'An archived domain cannot be activated.']);
        }

        return DB::transaction(function () use ($domain): Domain {
            $domain->forceFill(['status' => DomainStatus::Active])->save();

            return $domain->refresh();
        });
    }
}
