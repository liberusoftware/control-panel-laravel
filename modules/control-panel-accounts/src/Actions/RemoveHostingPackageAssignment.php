<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Actions;

use Liberu\ControlPanel\Accounts\Models\HostingPackageAssignment;

final class RemoveHostingPackageAssignment
{
    public function execute(HostingPackageAssignment $assignment): void
    {
        $assignment->delete();
    }
}
