<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Security\Actions\RecordFinding;
use Liberu\ControlPanel\Security\Actions\RecordSecurityResource;
use Liberu\ControlPanel\Security\Actions\ResolveSecurityFinding;
use Liberu\ControlPanel\Security\Actions\StoreSecret;
use Liberu\ControlPanel\Security\Actions\UpdateSecurityFinding;
use Liberu\ControlPanel\Security\Models\ComplianceStatus;
use Liberu\ControlPanel\Security\Models\HardeningControl;
use Liberu\ControlPanel\Security\Models\IntrusionControl;
use Liberu\ControlPanel\Security\Models\MalwareScan;
use Liberu\ControlPanel\Security\Models\MfaRbacPolicy;
use Liberu\ControlPanel\Security\Models\PatchRecord;
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

    public function show(Request $request, string $id): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = SecurityFinding::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-security-finding', 'attributes' => $item->toArray()]]);
    }

    public function store(Request $request, RecordFinding $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['subject_type' => ['required', 'string', 'max:120'], 'subject_id' => ['required', 'string', 'max:160'], 'code' => ['required', 'string', 'max:120'], 'severity' => ['required', 'in:critical,high,medium,low,info'], 'summary' => ['required', 'string', 'max:255'], 'evidence' => ['nullable', 'array']]);
        $item = $record->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($item)], 201);
    }

    public function resolve(Request $request, string $id, ResolveSecurityFinding $resolve): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $finding = SecurityFinding::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => self::resource($resolve->execute($finding))]);
    }

    public function update(Request $request, string $id, UpdateSecurityFinding $update): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $finding = SecurityFinding::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate(['subject_type' => ['sometimes', 'string', 'max:120'], 'subject_id' => ['sometimes', 'string', 'max:160'], 'code' => ['sometimes', 'string', 'max:120'], 'severity' => ['sometimes', 'in:critical,high,medium,low,info'], 'summary' => ['sometimes', 'string', 'max:255'], 'evidence' => ['sometimes', 'array']]);

        return response()->json(['data' => self::resource($update->execute($finding, $data))]);
    }

    public function hardening(Request $request, RecordSecurityResource $record): JsonResponse
    {
        return $this->record($request, $record, HardeningControl::class, ['subject_type' => ['required', 'string', 'max:120'], 'subject_id' => ['required', 'string', 'max:160'], 'control' => ['required', 'string', 'max:160'], 'desired' => ['required', 'boolean'], 'observed' => ['required', 'boolean'], 'status' => ['required', 'string', 'max:40'], 'evidence' => ['nullable', 'array']]);
    }

    public function patch(Request $request, RecordSecurityResource $record): JsonResponse
    {
        return $this->record($request, $record, PatchRecord::class, ['subject_type' => ['required', 'string', 'max:120'], 'subject_id' => ['required', 'string', 'max:160'], 'package' => ['required', 'string', 'max:255'], 'target_version' => ['required', 'string', 'max:80'], 'current_version' => ['nullable', 'string', 'max:80'], 'severity' => ['required', 'in:critical,high,medium,low,info'], 'status' => ['required', 'string', 'max:40']]);
    }

    public function policy(Request $request, RecordSecurityResource $record): JsonResponse
    {
        return $this->record($request, $record, MfaRbacPolicy::class, ['subject_type' => ['required', 'string', 'max:120'], 'subject_id' => ['required', 'string', 'max:160'], 'mfa_required' => ['required', 'boolean'], 'roles' => ['nullable', 'array'], 'permissions' => ['nullable', 'array'], 'status' => ['required', 'string', 'max:40']]);
    }

    public function secret(Request $request, StoreSecret $store): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['name' => ['required', 'string', 'max:160'], 'purpose' => ['nullable', 'string', 'max:160'], 'value' => ['required', 'string', 'max:10000'], 'expires_at' => ['nullable', 'date']]);
        $secret = $store->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $secret->getKey(), 'type' => 'control-panel-security-secret', 'attributes' => $secret->only(['name', 'purpose', 'version', 'status', 'expires_at'])]], 201);
    }

    public function malware(Request $request, RecordSecurityResource $record): JsonResponse
    {
        return $this->record($request, $record, MalwareScan::class, ['subject_type' => ['required', 'string', 'max:120'], 'subject_id' => ['required', 'string', 'max:160'], 'status' => ['required', 'string', 'max:40'], 'scanner' => ['required', 'string', 'max:120'], 'findings' => ['nullable', 'array']]);
    }

    public function intrusion(Request $request, RecordSecurityResource $record): JsonResponse
    {
        return $this->record($request, $record, IntrusionControl::class, ['subject_type' => ['required', 'string', 'max:120'], 'subject_id' => ['required', 'string', 'max:160'], 'kind' => ['required', 'string', 'max:120'], 'action' => ['required', 'string', 'max:80'], 'threshold' => ['required', 'integer', 'min:1'], 'window_seconds' => ['required', 'integer', 'min:1'], 'enabled' => ['sometimes', 'boolean']]);
    }

    public function compliance(Request $request, RecordSecurityResource $record): JsonResponse
    {
        return $this->record($request, $record, ComplianceStatus::class, ['framework' => ['required', 'string', 'max:120'], 'control' => ['required', 'string', 'max:120'], 'status' => ['required', 'string', 'max:40'], 'score' => ['nullable', 'integer', 'between:0,100'], 'evidence' => ['nullable', 'array']]);
    }

    /** @param array<string, array<int, mixed>> $rules */
    private function record(Request $request, RecordSecurityResource $record, string $model, array $rules): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = $record->execute($model, array_merge($request->validate($rules), ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-security-resource', 'attributes' => $item->attributesToArray()]], 201);
    }

    private static function resource(SecurityFinding $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-security-finding', 'attributes' => $item->only(['subject_type', 'subject_id', 'code', 'severity', 'status', 'summary', 'evidence'])];
    }
}
