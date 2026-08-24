<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Security\Actions\RecordFinding;
use Liberu\ControlPanel\Security\Models\SecurityFinding;
use Liberu\ControlPanel\Security\Queries\ListFindings;

final class SecurityFindingController
{
    public function index(Request $request, ListFindings $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $items = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $items->through(static fn (SecurityFinding $item): array => self::resource($item)), 'meta' => ['current_page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, RecordFinding $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['subject_type' => ['required', 'string', 'max:120'], 'subject_id' => ['required', 'string', 'max:160'], 'code' => ['required', 'string', 'max:120'], 'severity' => ['required', 'in:critical,high,medium,low,info'], 'summary' => ['required', 'string', 'max:255'], 'evidence' => ['nullable', 'array']]);
        $item = $record->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($item)], 201);
    }

    private static function resource(SecurityFinding $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-security-finding', 'attributes' => $item->only(['subject_type', 'subject_id', 'code', 'severity', 'status', 'summary', 'evidence'])];
    }
}
