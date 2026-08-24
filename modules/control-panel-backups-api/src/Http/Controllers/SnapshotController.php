<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Backups\Actions\CreateSnapshot;
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

    public function store(Request $request, CreateSnapshot $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['policy_id' => ['required', 'string', 'max:255'], 'location' => ['nullable', 'string', 'max:255'], 'metadata' => ['nullable', 'array']]);
        $policy = BackupPolicy::query()->where('team_id', $teamId)->findOrFail($data['policy_id']);
        $snapshot = $create->execute($policy, $data);

        return response()->json(['data' => self::resource($snapshot)], 201);
    }

    private static function resource(BackupSnapshot $snapshot): array
    {
        return ['id' => $snapshot->getKey(), 'type' => 'control-panel-backup-snapshot', 'attributes' => $snapshot->only(['policy_id', 'location', 'status', 'size_bytes', 'checksum', 'verified_at', 'metadata'])];
    }
}
