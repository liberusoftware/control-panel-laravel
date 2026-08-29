<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Liberu\ControlPanel\Backups\Models\BackupDestination;

final class DeleteDestination
{
    public function execute(BackupDestination $destination): void
    {
        $destination->delete();
    }
}
