<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNode;
use Liberu\ControlPanel\ControlCore\Queries\ListNodes;

final class NodeController
{
    public function index(Request $request, ListNodes $nodes): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $page = $nodes->execute(
            $teamId,
            $request->integer('per_page', 25),
        );

        return response()->json([
            'data' => $page->through(static fn ($node): array => [
                'id' => $node->getKey(),
                'type' => 'control-panel-node',
                'attributes' => $node->only(['name', 'hostname', 'platform', 'status', 'desired_state', 'observed_state', 'last_seen_at']),
            ]),
            'meta' => ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total()],
        ]);
    }

    public function store(Request $request, RegisterNode $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'hostname' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:80'],
            'credentials' => ['nullable', 'array'],
            'desired_state' => ['nullable', 'array'],
        ]);

        $node = $register->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json([
            'data' => [
                'id' => $node->getKey(),
                'type' => 'control-panel-node',
                'attributes' => $node->only(['name', 'hostname', 'platform', 'status']),
            ],
        ], 201);
    }
}
