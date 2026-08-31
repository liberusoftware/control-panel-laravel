<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Containers\Actions\DeleteWorkload;
use Liberu\ControlPanel\Containers\Actions\RecordContainerResource;
use Liberu\ControlPanel\Containers\Actions\RegisterContainerAsset;
use Liberu\ControlPanel\Containers\Actions\RegisterWorkload;
use Liberu\ControlPanel\Containers\Actions\StartWorkload;
use Liberu\ControlPanel\Containers\Actions\StopWorkload;
use Liberu\ControlPanel\Containers\Models\ContainerImage;
use Liberu\ControlPanel\Containers\Models\ContainerLifecycle;
use Liberu\ControlPanel\Containers\Models\ContainerLimit;
use Liberu\ControlPanel\Containers\Models\ContainerNetwork;
use Liberu\ControlPanel\Containers\Models\ContainerRegistry;
use Liberu\ControlPanel\Containers\Models\ContainerSecret;
use Liberu\ControlPanel\Containers\Models\ContainerVolume;
use Liberu\ControlPanel\Containers\Models\Workload;
use Liberu\ControlPanel\Containers\Queries\ListWorkloads;

final class WorkloadController
{
    /** @var array<string, class-string<Model>> */
    private const ASSET_MODELS = [
        'image' => ContainerImage::class,
        'registry' => ContainerRegistry::class,
        'network' => ContainerNetwork::class,
        'volume' => ContainerVolume::class,
        'secret' => ContainerSecret::class,
        'limit' => ContainerLimit::class,
        'lifecycle' => ContainerLifecycle::class,
    ];

    /** @var array<string, list<string>> */
    private const ASSET_FIELDS = [
        'image' => ['repository', 'tag', 'digest', 'size_bytes', 'architecture', 'status', 'metadata'],
        'registry' => ['name', 'endpoint', 'username', 'tls_verify', 'active'],
        'network' => ['name', 'driver', 'subnet', 'gateway', 'options', 'status'],
        'volume' => ['name', 'driver', 'mount_path', 'size_bytes', 'status', 'metadata'],
        'secret' => ['name', 'metadata', 'active'],
        'limit' => ['workload_id', 'cpu_millis', 'memory_bytes', 'pids', 'restart_policy'],
        'lifecycle' => ['workload_id', 'operation', 'status', 'requested_at', 'completed_at', 'details'],
    ];

    public function index(Request $request, ListWorkloads $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $items = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $items->through(static fn (Workload $item): array => self::resource($item)), 'meta' => ['current_page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = Workload::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => self::resource($item)]);
    }

    public function assets(Request $request): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        $data = $request->validate([
            'kind' => ['required', 'string', 'in:'.implode(',', array_keys(self::ASSET_MODELS))],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $model = self::ASSET_MODELS[$data['kind']];
        $items = $model::query()
            ->where('team_id', $teamId)
            ->latest()
            ->paginate($data['per_page'] ?? 25)
            ->withQueryString();

        return response()->json([
            'data' => $items->getCollection()->map(fn ($item): array => [
                'id' => $item->getKey(),
                'type' => 'control-panel-container-'.$data['kind'],
                'attributes' => $item->only(self::ASSET_FIELDS[$data['kind']]),
            ])->values(),
            'meta' => [
                'kind' => $data['kind'],
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
            'links' => [
                'first' => $items->url(1),
                'last' => $items->url($items->lastPage()),
                'prev' => $items->previousPageUrl(),
                'next' => $items->nextPageUrl(),
            ],
        ]);
    }

    public function store(Request $request, RegisterWorkload $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['node_id' => ['required', 'uuid'], 'name' => ['required', 'string', 'max:120'], 'image' => ['required', 'string', 'max:255'], 'specification' => ['nullable', 'array']]);
        $item = $register->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($item)], 201);
    }

    public function start(Request $request, Workload $workload, StartWorkload $start): JsonResponse
    {
        $this->assertTeam($request, $workload);

        return response()->json(['data' => self::resource($start->execute($workload))]);
    }

    public function stop(Request $request, Workload $workload, StopWorkload $stop): JsonResponse
    {
        $this->assertTeam($request, $workload);

        return response()->json(['data' => self::resource($stop->execute($workload))]);
    }

    public function delete(Request $request, Workload $workload, DeleteWorkload $delete): JsonResponse
    {
        $this->assertTeam($request, $workload);
        $delete->execute($workload);

        return response()->json(status: 204);
    }

    public function resourceRecord(Request $request, RecordContainerResource $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['workload_id' => ['nullable', 'uuid'], 'kind' => ['required', 'in:image,registry,network,volume,secret,limit,lifecycle'], 'name' => ['required', 'string', 'max:255'], 'status' => ['nullable', 'string', 'max:50'], 'spec' => ['nullable', 'array']]);
        $item = $record->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-container-resource', 'attributes' => $item->only(['workload_id', 'kind', 'name', 'status', 'spec'])]], 201);
    }

    public function asset(Request $request, RegisterContainerAsset $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['kind' => ['required', 'in:image,registry,network,volume,secret,limit,lifecycle'], 'payload' => ['required', 'array']]);
        $item = $register->execute(array_merge($data['payload'], ['kind' => $data['kind'], 'team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-container-'.$data['kind'], 'attributes' => $item->only(self::ASSET_FIELDS[$data['kind']])]], 201);
    }

    private static function resource(Workload $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-workload', 'attributes' => $item->only(['node_id', 'name', 'image', 'status', 'specification'])];
    }

    private function assertTeam(Request $request, Workload $workload): void
    {
        abort_if($request->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $workload->team_id === (string) $request->user()?->current_team_id, 404);
    }
}
