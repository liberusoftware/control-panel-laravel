<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Enums\SnapshotStatus;
use Liberu\ControlPanel\Backups\Events\SnapshotCreated;
use Liberu\ControlPanel\Backups\Models\BackupPolicy;
use Liberu\ControlPanel\Backups\Models\BackupSnapshot;

final readonly class CreateSnapshot
{
    public function __construct(private Dispatcher $events) {}

    /** @param array<string, mixed> $attributes */
    public function execute(BackupPolicy $policy, array $attributes = []): BackupSnapshot
    {
        if (! $policy->active) {
            throw ValidationException::withMessages(['policy' => 'Inactive backup policies cannot create snapshots.']);
        }

        return DB::transaction(function () use ($policy, $attributes): BackupSnapshot {
            $snapshot = BackupSnapshot::query()->create(['id' => (string) Str::uuid(), 'team_id' => $policy->team_id, 'policy_id' => $policy->getKey(), 'location' => $attributes['location'] ?? $policy->storage_driver, 'status' => SnapshotStatus::Queued, 'metadata' => $attributes['metadata'] ?? []]);
            $this->events->dispatch(new SnapshotCreated($snapshot));

            return $snapshot;
        });
    }
}
