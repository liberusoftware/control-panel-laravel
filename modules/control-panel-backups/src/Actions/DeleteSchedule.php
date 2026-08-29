<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Liberu\ControlPanel\Backups\Models\BackupSchedule;

final class DeleteSchedule
{
    public function execute(BackupSchedule $schedule): void
    {
        $schedule->delete();
    }
}
