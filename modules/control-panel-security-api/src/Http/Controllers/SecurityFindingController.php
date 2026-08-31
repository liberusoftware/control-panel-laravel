<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Security\Actions\ConfigureFail2ban;
use Liberu\ControlPanel\Security\Actions\RecordFail2banBan;
use Liberu\ControlPanel\Security\Actions\RecordFinding;
use Liberu\ControlPanel\Security\Actions\RecordSecurityResource;
use Liberu\ControlPanel\Security\Actions\ResolveSecurityFinding;
use Liberu\ControlPanel\Security\Actions\StoreSecret;
use Liberu\ControlPanel\Security\Actions\UnbanFail2banBan;
use Liberu\ControlPanel\Security\Actions\UpdateSecurityFinding;
use Liberu\ControlPanel\Security\Models\ComplianceStatus;
use Liberu\ControlPanel\Security\Models\Fail2banBan;
use Liberu\ControlPanel\Security\Models\Fail2banSetting;
use Liberu\ControlPanel\Security\Models\HardeningControl;
use Liberu\ControlPanel\Security\Models\IntrusionControl;
use Liberu\ControlPanel\Security\Models\MalwareScan;
use Liberu\ControlPanel\Security\Models\MfaRbacPolicy;
use Liberu\ControlPanel\Security\Models\PatchRecord;
use Liberu\ControlPanel\Security\Models\SecretRecord;
use Liberu\ControlPanel\Security\Models\SecurityFinding;
use Liberu\ControlPanel\Security\Queries\ListFindings;

final class SecurityFindingController
{
    /** @var array<class-string<Model>, list<string>> */
    private const RESOURCE_FIELDS = [
        ComplianceStatus::class => ['team_id', 'framework', 'control', 'status', 'score', 'evidence', 'assessed_at', 'expires_at'],
        HardeningControl::class => ['team_id', 'subject_type', 'subject_id', 'control', 'desired', 'observed', 'status', 'evidence', 'checked_at'],
        PatchRecord::class => ['team_id', 'subject_type', 'subject_id', 'package', 'current_version', 'target_version', 'severity', 'status', 'published_at', 'installed_at', 'metadata'],
        MfaRbacPolicy::class => ['team_id', 'subject_type', 'subject_id', 'mfa_required', 'roles', 'permissions', 'status', 'metadata'],
        MalwareScan::class => ['team_id', 'subject_type', 'subject_id', 'status', 'scanner', 'findings', 'started_at', 'finished_at'],
        IntrusionControl::class => ['team_id', 'subject_type', 'subject_id', 'kind', 'action', 'threshold', 'window_seconds', 'enabled', 'metadata'],
        SecretRecord::class => ['team_id', 'name', 'purpose', 'version', 'status', 'expires_at', 'rotated_at'],
    ];

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

        return response()->json(['data' => self::resource($item)]);
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

    public function patches(Request $request): JsonResponse
    {
        return $this->collection($request, PatchRecord::class);
    }

    public function policy(Request $request, RecordSecurityResource $record): JsonResponse
    {
        return $this->record($request, $record, MfaRbacPolicy::class, ['subject_type' => ['required', 'string', 'max:120'], 'subject_id' => ['required', 'string', 'max:160'], 'mfa_required' => ['required', 'boolean'], 'roles' => ['nullable', 'array'], 'permissions' => ['nullable', 'array'], 'status' => ['required', 'string', 'max:40']]);
    }

    public function policies(Request $request): JsonResponse
    {
        return $this->collection($request, MfaRbacPolicy::class);
    }

    public function secret(Request $request, StoreSecret $store): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['name' => ['required', 'string', 'max:160'], 'purpose' => ['nullable', 'string', 'max:160'], 'value' => ['required', 'string', 'max:10000'], 'expires_at' => ['nullable', 'date']]);
        $secret = $store->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $secret->getKey(), 'type' => 'control-panel-security-secret', 'attributes' => $secret->only(['name', 'purpose', 'version', 'status', 'expires_at'])]], 201);
    }

    public function secrets(Request $request): JsonResponse
    {
        return $this->collection($request, SecretRecord::class);
    }

    public function malware(Request $request, RecordSecurityResource $record): JsonResponse
    {
        return $this->record($request, $record, MalwareScan::class, ['subject_type' => ['required', 'string', 'max:120'], 'subject_id' => ['required', 'string', 'max:160'], 'status' => ['required', 'string', 'max:40'], 'scanner' => ['required', 'string', 'max:120'], 'findings' => ['nullable', 'array']]);
    }

    public function malwareScans(Request $request): JsonResponse
    {
        return $this->collection($request, MalwareScan::class);
    }

    public function intrusion(Request $request, RecordSecurityResource $record): JsonResponse
    {
        return $this->record($request, $record, IntrusionControl::class, ['subject_type' => ['required', 'string', 'max:120'], 'subject_id' => ['required', 'string', 'max:160'], 'kind' => ['required', 'string', 'max:120'], 'action' => ['required', 'string', 'max:80'], 'threshold' => ['required', 'integer', 'min:1'], 'window_seconds' => ['required', 'integer', 'min:1'], 'enabled' => ['sometimes', 'boolean']]);
    }

    public function intrusionControls(Request $request): JsonResponse
    {
        return $this->collection($request, IntrusionControl::class);
    }

    public function fail2ban(Request $request, ConfigureFail2ban $configure): JsonResponse
    {
        $teamId = $this->teamId($request);
        $data = $request->validate([
            'jail_name' => ['required', 'string', 'max:120'], 'enabled' => ['sometimes', 'boolean'],
            'max_retry' => ['sometimes', 'integer', 'min:1', 'max:100000'], 'find_time' => ['sometimes', 'integer', 'min:1', 'max:31536000'],
            'ban_time' => ['sometimes', 'integer', 'min:1', 'max:31536000'], 'whitelist_ips' => ['nullable', 'array'], 'whitelist_ips.*' => ['ip'],
        ]);

        return response()->json(['data' => self::fail2banSettingResource($configure->execute([...$data, 'team_id' => $teamId]))], 201);
    }

    public function fail2banBans(Request $request, string $jail, RecordFail2banBan $record): JsonResponse
    {
        $setting = Fail2banSetting::query()->where('team_id', $this->teamId($request))->where('jail_name', $jail)->firstOrFail();
        $data = $request->validate(['ip_address' => ['required', 'ip'], 'banned_at' => ['sometimes', 'date'], 'ban_count' => ['sometimes', 'integer', 'min:1'], 'reason' => ['nullable', 'string', 'max:2000']]);

        return response()->json(['data' => self::fail2banBanResource($record->execute($setting, $data))], 201);
    }

    public function listFail2banBans(Request $request, string $jail): JsonResponse
    {
        $teamId = $this->teamId($request);
        Fail2banSetting::query()->where('team_id', $teamId)->where('jail_name', $jail)->firstOrFail();
        $bans = Fail2banBan::query()->where('team_id', $teamId)->where('jail_name', $jail)->latest('banned_at')->limit(100)->get();

        return response()->json(['data' => $bans->map(static fn (Fail2banBan $ban): array => self::fail2banBanResource($ban))]);
    }

    public function unbanFail2ban(Request $request, string $ban, UnbanFail2banBan $unban): JsonResponse
    {
        $item = Fail2banBan::query()->whereKey($ban)->where('team_id', $this->teamId($request))->firstOrFail();

        return response()->json(['data' => self::fail2banBanResource($unban->execute($item))]);
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

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-security-resource', 'attributes' => $item->only(self::RESOURCE_FIELDS[$model])]], 201);
    }

    private static function resource(SecurityFinding $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-security-finding', 'attributes' => $item->only(['subject_type', 'subject_id', 'code', 'severity', 'status', 'summary', 'evidence'])];
    }

    private static function fail2banSettingResource(Fail2banSetting $setting): array
    {
        return ['id' => $setting->getKey(), 'type' => 'control-panel-fail2ban-setting', 'attributes' => $setting->only(['team_id', 'jail_name', 'enabled', 'max_retry', 'find_time', 'ban_time', 'whitelist_ips'])];
    }

    private static function fail2banBanResource(Fail2banBan $ban): array
    {
        return ['id' => $ban->getKey(), 'type' => 'control-panel-fail2ban-ban', 'attributes' => $ban->only(['team_id', 'jail_name', 'ip_address', 'banned_at', 'unbanned_at', 'ban_count', 'reason'])];
    }

    private function collection(Request $request, string $model): JsonResponse
    {
        $teamId = $this->teamId($request);
        $items = $model::query()->where('team_id', $teamId)->latest()->limit(min(max($request->integer('limit', 100), 1), 100))->get();

        return response()->json(['data' => $items->map(static fn ($item): array => [
            'id' => $item->getKey(), 'type' => 'control-panel-security-resource', 'attributes' => $item->only(self::RESOURCE_FIELDS[$model]),
        ])->all()]);
    }

    private function teamId(Request $request): string
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
