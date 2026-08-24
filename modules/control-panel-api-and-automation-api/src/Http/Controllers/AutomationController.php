<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterAutomation;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationDefinition;
use Liberu\ControlPanel\ApiAutomation\Queries\ListAutomations;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterApiCredential;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\StartOrchestration;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationTemplate;

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

    public function credential(Request $request, RegisterApiCredential $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'scopes' => ['nullable', 'array'], 'scopes.*' => ['string', 'max:120'], 'secret' => ['nullable', 'string', 'max:512'], 'expires_at' => ['nullable', 'date']]);
        $credential = $register->execute(array_merge($data, ['team_id' => $teamId]));
        return response()->json(['data' => ['id' => $credential->getKey(), 'type' => 'control-panel-api-credential', 'attributes' => $credential->only(['name', 'scopes', 'status', 'expires_at'])]], 201);
    }

    public function webhook(Request $request, RegisterWebhook $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'url' => ['required', 'url', 'starts_with:https://'], 'events' => ['nullable', 'array'], 'events.*' => ['string', 'max:120'], 'secret' => ['nullable', 'string', 'max:512'], 'retry_limit' => ['nullable', 'integer', 'between:0,20']]);
        $webhook = $register->execute(array_merge($data, ['team_id' => $teamId]));
        return response()->json(['data' => ['id' => $webhook->getKey(), 'type' => 'control-panel-automation-webhook', 'attributes' => $webhook->only(['name', 'url', 'events', 'status', 'retry_limit'])]], 201);
    }

    public function run(Request $request, string $template, StartOrchestration $start): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = AutomationTemplate::query()->whereKey($template)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate(['input' => ['nullable', 'array']]);
        $run = $start->execute($item, $data['input'] ?? [], $teamId, $request->header('Idempotency-Key'));
        return response()->json(['data' => ['id' => $run->getKey(), 'type' => 'control-panel-orchestration-run', 'attributes' => $run->only(['template_id', 'status', 'input', 'started_at'])]], 202);
    }

    private static function resource(AutomationDefinition $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-automation-definition', 'attributes' => $item->only(['name', 'kind', 'status', 'schedule', 'definition'])];
    }
}
