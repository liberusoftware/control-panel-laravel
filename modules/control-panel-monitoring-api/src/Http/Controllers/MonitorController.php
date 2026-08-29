<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Monitoring\Actions\CancelMaintenanceWindow;
use Liberu\ControlPanel\Monitoring\Actions\RecordMonitoringEvent;
use Liberu\ControlPanel\Monitoring\Actions\RecordMonitoringResource;
use Liberu\ControlPanel\Monitoring\Actions\RegisterMonitor;
use Liberu\ControlPanel\Monitoring\Actions\ResolveMonitoringEvent;
use Liberu\ControlPanel\Monitoring\Models\MaintenanceWindow;
use Liberu\ControlPanel\Monitoring\Models\Monitor;
use Liberu\ControlPanel\Monitoring\Models\MonitoringEvent;
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

    public function show(Request $request, string $id): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = Monitor::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-monitor', 'attributes' => $item->toArray()]]);
    }

    public function store(Request $request, RegisterMonitor $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['subject_type' => ['required', 'string', 'max:120'], 'subject_id' => ['required', 'string', 'max:160'], 'name' => ['required', 'string', 'max:120'], 'metrics' => ['nullable', 'array']]);
        $item = $register->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($item)], 201);
    }

    public function event(Request $request, RecordMonitoringEvent $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['monitor_id' => ['nullable', 'uuid'], 'kind' => ['required', 'in:metric,log,uptime,capacity,alert,incident,maintenance,status'], 'status' => ['nullable', 'string', 'max:50'], 'payload' => ['nullable', 'array'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date']]);
        $item = $record->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-monitoring-event', 'attributes' => $item->only(['monitor_id', 'kind', 'status', 'payload', 'starts_at', 'ends_at'])]], 201);
    }

    public function record(Request $request, RecordMonitoringResource $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['kind' => ['required', 'in:metric,log,uptime,capacity,alert,incident,maintenance,status'], 'payload' => ['required', 'array']]);
        $item = $record->execute(array_merge($data['payload'], ['kind' => $data['kind'], 'team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-monitoring-resource', 'attributes' => $item->toArray()]], 201);
    }

    public function resolveEvent(Request $request, string $id, ResolveMonitoringEvent $resolve): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $event = MonitoringEvent::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();

        $event = $resolve->execute($event);

        return response()->json(['data' => ['id' => $event->getKey(), 'type' => 'control-panel-monitoring-event', 'attributes' => $event->only(['monitor_id', 'kind', 'status', 'payload', 'starts_at', 'ends_at'])]]);
    }

    public function cancelMaintenance(Request $request, MaintenanceWindow $window, CancelMaintenanceWindow $cancel): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        abort_unless((string) $window->team_id === (string) $teamId, 404);

        return response()->json(['data' => ['id' => $window->getKey(), 'type' => 'control-panel-monitoring-maintenance', 'attributes' => $cancel->execute($window)->only(['name', 'starts_at', 'ends_at', 'scope', 'status', 'details'])]]);
    }

    private static function resource(Monitor $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-monitor', 'attributes' => $item->only(['subject_type', 'subject_id', 'name', 'status', 'last_checked_at', 'metrics'])];
    }
}
