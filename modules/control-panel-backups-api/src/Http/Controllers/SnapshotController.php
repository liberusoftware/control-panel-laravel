<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Backups\Actions\CreateDestination;
use Liberu\ControlPanel\Backups\Actions\CreateSchedule;
use Liberu\ControlPanel\Backups\Actions\CreateSnapshot;
use Liberu\ControlPanel\Backups\Actions\RecordBackupFeature;
use Liberu\ControlPanel\Backups\Actions\RequestRestore;
use Liberu\ControlPanel\Backups\Actions\VerifySnapshot;
use Liberu\ControlPanel\Backups\Models\BackupPolicy;
use Liberu\ControlPanel\Backups\Models\BackupSnapshot;
use Liberu\ControlPanel\Backups\Queries\ListSnapshots;

final class SnapshotController
{
    public function index(Request $request, ListSnapshots $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $snapshots = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $snapshots->through(static fn (BackupSnapshot $snapshot): array => self::resource($snapshot)), 'meta' => ['current_page' => $snapshots->currentPage(), 'per_page' => $snapshots->perPage(), 'total' => $snapshots->total()]]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = BackupSnapshot::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-backup-snapshot', 'attributes' => $item->toArray()]]);
    }

    public function store(Request $request, CreateSnapshot $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['policy_id' => ['required', 'string', 'max:255'], 'location' => ['nullable', 'string', 'max:255'], 'metadata' => ['nullable', 'array']]);
        $policy = BackupPolicy::query()->where('team_id', $teamId)->findOrFail($data['policy_id']);
        $snapshot = $create->execute($policy, $data);

        return response()->json(['data' => self::resource($snapshot)], 201);
    }

    public function destination(Request $request, CreateDestination $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'driver' => ['required', 'in:local,s3,sftp,ftp'], 'config' => ['nullable', 'array'], 'retention_days' => ['nullable', 'integer', 'min:1'], 'default' => ['sometimes', 'boolean']]);
        $destination = $create->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $destination->getKey(), 'type' => 'control-panel-backup-destination', 'attributes' => $destination->only(['name', 'driver', 'retention_days', 'default', 'active'])]], 201);
    }

    public function schedule(Request $request, CreateSchedule $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['policy_id' => ['required', 'uuid'], 'cron' => ['required', 'string', 'max:120'], 'timezone' => ['nullable', 'timezone']]);
        $policy = BackupPolicy::query()->where('team_id', $teamId)->findOrFail($data['policy_id']);
        $schedule = $create->execute($policy, $data['cron'], $data['timezone'] ?? 'UTC');

        return response()->json(['data' => ['id' => $schedule->getKey(), 'type' => 'control-panel-backup-schedule', 'attributes' => $schedule->only(['policy_id', 'cron', 'timezone', 'active', 'next_run_at'])]], 201);
    }

    public function restore(Request $request, string $snapshot, RequestRestore $restore): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = BackupSnapshot::query()->whereKey($snapshot)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate(['target' => ['required', 'string', 'max:1024'], 'options' => ['nullable', 'array']]);
        $run = $restore->execute($item, (string) $teamId, $data['target'], $data['options'] ?? []);

        return response()->json(['data' => ['id' => $run->getKey(), 'type' => 'control-panel-backup-restore', 'attributes' => $run->only(['snapshot_id', 'target', 'status', 'options'])]], 202);
    }

    public function verify(Request $request, string $snapshot, VerifySnapshot $verify): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = BackupSnapshot::query()->whereKey($snapshot)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate(['checksum' => ['required', 'string', 'max:255']]);

        return response()->json(['data' => self::resource($verify->execute($item, $data['checksum']))]);
    }

    public function feature(Request $request, RecordBackupFeature $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['kind' => ['required', 'in:execution,encryption,offsite'], 'payload' => ['required', 'array']]);
        $item = $record->execute(array_merge($data['payload'], ['kind' => $data['kind'], 'team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-backup-'.$data['kind'], 'attributes' => $item->toArray()]], 201);
    }

    private static function resource(BackupSnapshot $snapshot): array
    {
        return ['id' => $snapshot->getKey(), 'type' => 'control-panel-backup-snapshot', 'attributes' => $snapshot->only(['policy_id', 'location', 'status', 'size_bytes', 'checksum', 'verified_at', 'metadata'])];
    }
}
