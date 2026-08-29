<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Enums\SnapshotStatus;
use Liberu\ControlPanel\Backups\Models\BackupSnapshot;

final class DeleteSnapshot
{
    public function execute(BackupSnapshot $snapshot): void
    {
        if ($snapshot->status === SnapshotStatus::Running) {
            throw ValidationException::withMessages(['snapshot' => 'A running snapshot cannot be deleted.']);
        }

        $snapshot->delete();
    }
}
