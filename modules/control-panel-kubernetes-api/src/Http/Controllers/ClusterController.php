<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Kubernetes\Actions\RegisterCluster;
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

    public function store(Request $request, RegisterCluster $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'endpoint' => ['required', 'url', 'max:255']]);
        $item = $register->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($item)], 201);
    }

    private static function resource(Cluster $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-kubernetes-cluster', 'attributes' => $item->only(['name', 'endpoint', 'status'])];
    }
}
