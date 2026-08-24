<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterAutomation;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationDefinition;
use Liberu\ControlPanel\ApiAutomation\Queries\ListAutomations;

final class AutomationController
{
    public function index(Request $request, ListAutomations $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $items = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $items->through(static fn (AutomationDefinition $item): array => self::resource($item)), 'meta' => ['current_page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, RegisterAutomation $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'kind' => ['required', 'string', 'max:60'], 'schedule' => ['nullable', 'string', 'max:120'], 'definition' => ['nullable', 'array']]);
        $item = $register->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($item)], 201);
    }

    private static function resource(AutomationDefinition $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-automation-definition', 'attributes' => $item->only(['name', 'kind', 'status', 'schedule', 'definition'])];
    }
}
