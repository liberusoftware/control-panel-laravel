<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Monitoring\Actions\RegisterMonitor;
use Liberu\ControlPanel\Monitoring\Models\Monitor;
use Liberu\ControlPanel\Monitoring\Queries\ListMonitors;

final class MonitorController
{
    public function index(Request $request, ListMonitors $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $items = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $items->through(static fn (Monitor $item): array => self::resource($item)), 'meta' => ['current_page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, RegisterMonitor $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['subject_type' => ['required', 'string', 'max:120'], 'subject_id' => ['required', 'string', 'max:160'], 'name' => ['required', 'string', 'max:120'], 'metrics' => ['nullable', 'array']]);
        $item = $register->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($item)], 201);
    }

    private static function resource(Monitor $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-monitor', 'attributes' => $item->only(['subject_type', 'subject_id', 'name', 'status', 'last_checked_at', 'metrics'])];
    }
}
