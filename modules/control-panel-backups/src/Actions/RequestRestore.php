<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Enums\RestoreStatus;
use Liberu\ControlPanel\Backups\Models\BackupRestore;
use Liberu\ControlPanel\Backups\Models\BackupSnapshot;

final class RequestRestore
{
    /** @param array<string, mixed> $options */
    public function execute(BackupSnapshot $snapshot, string $teamId, string $target, array $options = []): BackupRestore
    {
        $target = trim($target);
        if ($target === '') {
            throw ValidationException::withMessages(['target' => 'A restore target is required.']);
        }

        return BackupRestore::query()->create(['id' => (string) Str::uuid(), 'team_id' => $teamId, 'snapshot_id' => $snapshot->getKey(), 'target' => $target, 'status' => RestoreStatus::Queued, 'options' => $options]);
    }
}
