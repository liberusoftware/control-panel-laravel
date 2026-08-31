<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Liberu\ControlPanel\WebHosting\Models\Subdomain;

final class UpdateSubdomain
{
    /** @param array<string, mixed> $attributes */
    public function execute(Subdomain $subdomain, array $attributes): Subdomain
    {
        $subdomain->fill($attributes);
        $subdomain->save();

        return $subdomain->refresh();
    }
}
