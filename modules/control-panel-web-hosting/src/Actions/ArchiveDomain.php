<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Enums\DomainStatus;
use Liberu\ControlPanel\WebHosting\Models\Domain;

final class ArchiveDomain
{
    public function execute(Domain $domain): Domain
    {
        if ($domain->status === DomainStatus::Archived) {
            throw ValidationException::withMessages(['domain' => 'The domain is already archived.']);
        }

        return DB::transaction(function () use ($domain): Domain {
            $domain->forceFill(['status' => DomainStatus::Archived])->save();

            return $domain->refresh();
        });
    }
}
