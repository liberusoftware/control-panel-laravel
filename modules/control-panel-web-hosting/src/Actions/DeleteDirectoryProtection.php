<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Liberu\ControlPanel\WebHosting\Models\DirectoryProtection;

final class DeleteDirectoryProtection
{
    public function execute(DirectoryProtection $protection): void
    {
        $protection->delete();
    }
}
