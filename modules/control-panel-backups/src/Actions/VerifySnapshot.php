<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Liberu\ControlPanel\Backups\Enums\SnapshotStatus;
use Liberu\ControlPanel\Backups\Events\SnapshotVerified;
use Liberu\ControlPanel\Backups\Models\BackupSnapshot;

final readonly class VerifySnapshot
{
    public function __construct(private Dispatcher $events) {}

    public function execute(BackupSnapshot $snapshot, string $checksum): BackupSnapshot
    {
        return DB::transaction(function () use ($snapshot, $checksum): BackupSnapshot {
            $snapshot->update(['status' => SnapshotStatus::Verified, 'checksum' => trim($checksum), 'verified_at' => now()]);
            $snapshot = $snapshot->refresh();
            $this->events->dispatch(new SnapshotVerified($snapshot));

            return $snapshot;
        });
    }
}
