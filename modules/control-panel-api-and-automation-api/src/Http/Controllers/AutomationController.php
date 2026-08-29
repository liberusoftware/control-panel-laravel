<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ApiAutomation\Actions\CreateAutomationSchedule;
use Liberu\ControlPanel\ApiAutomation\Actions\CreateAutomationTemplate;
use Liberu\ControlPanel\ApiAutomation\Actions\PauseWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\RecordBillingProvisioningEvent;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterApiCredential;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterAutomation;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterAutomationCommand;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\ResumeWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\RevokeApiCredential;
use Liberu\ControlPanel\ApiAutomation\Actions\StartOrchestration;
use Liberu\ControlPanel\ApiAutomation\Actions\UpdateApiCredential;
use Liberu\ControlPanel\ApiAutomation\Actions\UpdateWebhook;
use Liberu\ControlPanel\ApiAutomation\Models\ApiCredential;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationDefinition;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationTemplate;
use Liberu\ControlPanel\ApiAutomation\Models\WebhookEndpoint;
use Liberu\ControlPanel\ApiAutomation\Queries\ListAutomations;

final class AutomationController
{
    public function index(Request $request, ListAutomations $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $items = $list->execute($teamId, self::perPage($request));

        return response()->json(['data' => $items->through(static fn (AutomationDefinition $item): array => self::resource($item)), 'meta' => ['current_page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = AutomationDefinition::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-automation', 'attributes' => $item->toArray()]]);
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

    public function revokeCredential(Request $request, string $credential, RevokeApiCredential $revoke): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = ApiCredential::query()->whereKey($credential)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => self::credentialResource($revoke->execute($item))]);
    }

    public function updateCredential(Request $request, string $credential, UpdateApiCredential $update): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = ApiCredential::query()->whereKey($credential)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:120'], 'scopes' => ['sometimes', 'array'], 'scopes.*' => ['string', 'max:120'], 'expires_at' => ['sometimes', 'nullable', 'date']]);

        return response()->json(['data' => self::credentialResource($update->execute($item, $data))]);
    }

    public function updateWebhook(Request $request, string $webhook, UpdateWebhook $update): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = WebhookEndpoint::query()->whereKey($webhook)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'url' => ['sometimes', 'url', 'starts_with:https://', 'max:2048'],
            'events' => ['sometimes', 'array'],
            'events.*' => ['string', 'max:120'],
            'retry_limit' => ['sometimes', 'integer', 'between:0,20'],
        ]);

        return response()->json(['data' => self::webhookResource($update->execute($item, $data))]);
    }

    public function pauseWebhook(Request $request, string $webhook, PauseWebhook $pause): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = WebhookEndpoint::query()->whereKey($webhook)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => self::webhookResource($pause->execute($item))]);
    }

    public function resumeWebhook(Request $request, string $webhook, ResumeWebhook $resume): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = WebhookEndpoint::query()->whereKey($webhook)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => self::webhookResource($resume->execute($item))]);
    }

    public function run(Request $request, string $template, StartOrchestration $start): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        if (strlen($idempotencyKey) < 8 || strlen($idempotencyKey) > 255) {
            throw ValidationException::withMessages(['Idempotency-Key' => 'An Idempotency-Key between 8 and 255 characters is required.']);
        }
        $item = AutomationTemplate::query()->whereKey($template)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate(['input' => ['nullable', 'array']]);
        $run = $start->execute($item, $data['input'] ?? [], $teamId, $idempotencyKey);

        return response()->json(['data' => ['id' => $run->getKey(), 'type' => 'control-panel-orchestration-run', 'attributes' => $run->only(['template_id', 'status', 'input', 'started_at'])]], 202);
    }

    public function template(Request $request, CreateAutomationTemplate $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'version' => ['required', 'string', 'max:40'], 'description' => ['nullable', 'string'], 'inputs' => ['nullable', 'array'], 'steps' => ['required', 'array', 'min:1'], 'active' => ['sometimes', 'boolean']]);
        $item = $create->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::templateResource($item)], 201);
    }

    public function schedule(Request $request, CreateAutomationSchedule $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'cron' => ['required', 'string', 'max:120'], 'timezone' => ['nullable', 'timezone'], 'template_id' => ['required', 'uuid'], 'next_run_at' => ['nullable', 'date']]);
        $item = $create->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-automation-schedule', 'attributes' => $item->only(['name', 'cron', 'timezone', 'template_id', 'status', 'next_run_at'])]], 201);
    }

    public function command(Request $request, RegisterAutomationCommand $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string'], 'command' => ['required', 'string', 'max:255'], 'arguments' => ['nullable', 'array'], 'enabled' => ['sometimes', 'boolean']]);
        $item = $register->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-automation-command', 'attributes' => $item->only(['name', 'description', 'command', 'arguments', 'enabled', 'last_run_at'])]], 201);
    }

    public function billingEvent(Request $request, RecordBillingProvisioningEvent $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['external_id' => ['required', 'string', 'max:255'], 'event_type' => ['required', 'string', 'max:120'], 'payload' => ['required', 'array']]);
        $item = $record->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-billing-provisioning-event', 'attributes' => $item->only(['external_id', 'event_type', 'payload', 'status', 'processed_at', 'error'])]], 202);
    }

    private static function resource(AutomationDefinition $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-automation-definition', 'attributes' => $item->only(['name', 'kind', 'status', 'schedule', 'definition'])];
    }

    private static function credentialResource(ApiCredential $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-api-credential', 'attributes' => $item->only(['name', 'scopes', 'status', 'expires_at'])];
    }

    private static function templateResource(AutomationTemplate $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-automation-template', 'attributes' => $item->only(['name', 'version', 'description', 'inputs', 'steps', 'active'])];
    }

    private static function webhookResource(WebhookEndpoint $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-automation-webhook', 'attributes' => $item->only(['name', 'url', 'events', 'status', 'retry_limit', 'failure_count', 'last_delivered_at'])];
    }

    private static function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 25), 1), 100);
    }
}
