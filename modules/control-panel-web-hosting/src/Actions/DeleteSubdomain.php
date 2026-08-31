<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Liberu\ControlPanel\WebHosting\Models\Subdomain;

final class DeleteSubdomain
{
    public function execute(Subdomain $subdomain): void
    {
        $subdomain->delete();
    }
}
