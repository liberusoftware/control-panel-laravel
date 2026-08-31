<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Liberu\ControlPanel\WebHosting\Models\DirectoryProtectionUser;

final class RemoveDirectoryProtectionUser
{
    public function execute(DirectoryProtectionUser $user): void
    {
        $user->delete();
    }
}
