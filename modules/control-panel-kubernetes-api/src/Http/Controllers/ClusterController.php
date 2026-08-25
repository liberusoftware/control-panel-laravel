<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Kubernetes\Actions\ArchiveCluster;
use Liberu\ControlPanel\Kubernetes\Actions\RecordKubernetesResource;
use Liberu\ControlPanel\Kubernetes\Actions\RegisterCluster;
use Liberu\ControlPanel\Kubernetes\Actions\RegisterKubernetesAsset;
use Liberu\ControlPanel\Kubernetes\Actions\SuspendCluster;
use Liberu\ControlPanel\Kubernetes\Models\Cluster;
use Liberu\ControlPanel\Kubernetes\Queries\ListClusters;

final class ClusterController
{
    public function index(Request $request, ListClusters $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $items = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $items->through(static fn (Cluster $item): array => self::resource($item)), 'meta' => ['current_page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = Cluster::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-cluster', 'attributes' => $item->toArray()]]);
    }

    public function store(Request $request, RegisterCluster $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'endpoint' => ['required', 'url', 'max:255']]);
        $item = $register->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($item)], 201);
    }

    public function suspend(Request $request, Cluster $cluster, SuspendCluster $suspend): JsonResponse
    {
        $this->assertTeam($request, $cluster);

        return response()->json(['data' => self::resource($suspend->execute($cluster))]);
    }

    public function archive(Request $request, Cluster $cluster, ArchiveCluster $archive): JsonResponse
    {
        $this->assertTeam($request, $cluster);

        return response()->json(['data' => self::resource($archive->execute($cluster))]);
    }

    public function resourceRecord(Request $request, RecordKubernetesResource $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['cluster_id' => ['nullable', 'uuid'], 'kind' => ['required', 'in:node,namespace,rbac,workload,ingress,helm,storage,autoscaling,upgrade,cluster-view'], 'name' => ['required', 'string', 'max:255'], 'namespace' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'string', 'max:50'], 'spec' => ['nullable', 'array']]);
        $item = $record->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-kubernetes-resource', 'attributes' => $item->only(['cluster_id', 'kind', 'name', 'namespace', 'status', 'spec'])]], 201);
    }

    public function asset(Request $request, RegisterKubernetesAsset $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['kind' => ['required', 'in:node,namespace,rbac,workload,ingress,helm,storage,autoscaling,upgrade,cluster-view'], 'payload' => ['required', 'array']]);
        $item = $register->execute(array_merge($data['payload'], ['kind' => $data['kind'], 'team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-kubernetes-'.$data['kind'], 'attributes' => $item->toArray()]], 201);
    }

    private static function resource(Cluster $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-kubernetes-cluster', 'attributes' => $item->only(['name', 'endpoint', 'status'])];
    }

    private function assertTeam(Request $request, Cluster $cluster): void
    {
        abort_unless((string) $cluster->team_id === (string) $request->user()?->current_team_id, 404);
    }
}
