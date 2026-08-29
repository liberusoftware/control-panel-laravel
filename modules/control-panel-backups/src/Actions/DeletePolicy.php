<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Liberu\ControlPanel\Backups\Models\BackupPolicy;

final class DeletePolicy
{
    public function execute(BackupPolicy $policy): void
    {
        $policy->delete();
    }
}
