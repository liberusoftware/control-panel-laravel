<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Actions\ArchiveZone;
use Liberu\ControlPanel\Dns\Actions\CheckDnsPropagation;
use Liberu\ControlPanel\Dns\Actions\CheckDnsResolution;
use Liberu\ControlPanel\Dns\Actions\CreateRecord;
use Liberu\ControlPanel\Dns\Actions\CreateZone;
use Liberu\ControlPanel\Dns\Actions\DeleteRecord;
use Liberu\ControlPanel\Dns\Actions\RecordDnsCheck;
use Liberu\ControlPanel\Dns\Actions\RegisterDnsFeature;
use Liberu\ControlPanel\Dns\Actions\SuspendZone;
use Liberu\ControlPanel\Dns\Actions\UpdateRecord;
use Liberu\ControlPanel\Dns\Actions\UpdateZone;
use Liberu\ControlPanel\Dns\Actions\ValidateRecord;
use Liberu\ControlPanel\Dns\Models\Record;
use Liberu\ControlPanel\Dns\Models\Zone;
use Liberu\ControlPanel\Dns\Queries\ListZones;

final class ZoneController
{
    /** @var array<string, list<string>> */
    private const FEATURE_FIELDS = [
        'template' => ['name', 'records', 'active'],
        'dnssec' => ['zone_id', 'key_tag', 'algorithm', 'digest_type', 'digest', 'public_key', 'active', 'rotated_at'],
        'provider' => ['name', 'driver', 'endpoint', 'settings', 'active'],
        'validation' => ['zone_id', 'record_id', 'status', 'resolver', 'expected', 'observed', 'checked_at', 'details'],
        'propagation' => ['zone_id', 'record_id', 'status', 'nameservers', 'results', 'checked_at'],
    ];

    public function index(Request $request, ListZones $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $zones = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $zones->through(static fn (Zone $zone): array => self::resource($zone)), 'meta' => ['current_page' => $zones->currentPage(), 'per_page' => $zones->perPage(), 'total' => $zones->total()]]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = Zone::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => self::resource($item)]);
    }

    public function store(Request $request, CreateZone $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['domain' => ['required', 'string', 'max:253'], 'provider' => ['nullable', 'string', 'max:100'], 'dnssec_enabled' => ['sometimes', 'boolean'], 'metadata' => ['nullable', 'array']]);
        $zone = $create->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($zone)], 201);
    }

    public function update(Request $request, string $id, UpdateZone $update): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $zone = Zone::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate([
            'domain' => ['sometimes', 'string', 'max:253'],
            'provider' => ['sometimes', 'nullable', 'string', 'max:100'],
            'dnssec_enabled' => ['sometimes', 'boolean'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ]);

        return response()->json(['data' => self::resource($update->execute($zone, $data))]);
    }

    public function record(Request $request, CreateRecord $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['zone_id' => ['required', 'uuid'], 'name' => ['nullable', 'string', 'max:253'], 'type' => ['required', 'string'], 'content' => ['required', 'string', 'max:4096'], 'ttl' => ['nullable', 'integer', 'min:60'], 'priority' => ['nullable', 'integer', 'min:0'], 'metadata' => ['nullable', 'array']]);
        $item = $create->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-dns-record', 'attributes' => $item->only(['zone_id', 'name', 'type', 'content', 'ttl', 'priority', 'metadata'])]], 201);
    }

    public function validateRecord(Request $request, ValidateRecord $validate): JsonResponse
    {
        $data = $request->validate([
            'record_type' => ['required', 'in:A,AAAA,CNAME,MX,TXT,NS,PTR,SRV,CAA'],
            'name' => ['required', 'string', 'max:253', 'regex:/^(@|[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)$/'],
            'value' => ['required', 'string', 'max:1000'],
            'ttl' => ['nullable', 'integer', 'between:60,86400'],
            'priority' => ['nullable', 'integer', 'between:0,65535'],
        ]);

        return response()->json(['success' => true] + $validate->execute($data));
    }

    public function updateRecord(Request $request, string $id, UpdateRecord $update): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $record = Record::query()->whereKey($id)->whereHas('zone', fn ($query) => $query->where('team_id', $teamId))->with('zone')->firstOrFail();
        $data = $request->validate([
            'zone_id' => ['sometimes', 'uuid'], 'name' => ['sometimes', 'string', 'max:253'],
            'type' => ['sometimes', 'in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA'], 'content' => ['sometimes', 'string', 'max:4096'],
            'ttl' => ['sometimes', 'integer', 'between:60,86400'], 'priority' => ['sometimes', 'nullable', 'integer', 'between:0,65535'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ]);

        return response()->json(['data' => self::recordResource($update->execute($record, $data))]);
    }

    public function deleteRecord(Request $request, string $id, DeleteRecord $delete): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $record = Record::query()->whereKey($id)->whereHas('zone', fn ($query) => $query->where('team_id', $teamId))->firstOrFail();
        $delete->execute($record);

        return response()->json(status: 204);
    }

    public function bulkRecords(Request $request, CreateRecord $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate([
            'zone_id' => ['required', 'uuid'],
            'records' => ['required', 'array', 'min:1', 'max:50'],
            'records.*.name' => ['required', 'string', 'max:253', 'regex:/^(@|[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)$/'],
            'records.*.type' => ['required', 'string', 'in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA'],
            'records.*.content' => ['required', 'string', 'max:4096'],
            'records.*.ttl' => ['nullable', 'integer', 'min:60', 'max:86400'],
            'records.*.priority' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'records.*.metadata' => ['nullable', 'array'],
        ]);

        Zone::query()->whereKey($data['zone_id'])->where('team_id', $teamId)->firstOrFail();
        $created = [];
        $errors = [];

        foreach ($data['records'] as $index => $record) {
            try {
                $item = $create->execute(array_merge($record, ['zone_id' => $data['zone_id'], 'team_id' => $teamId]));
                $created[] = self::recordResource($item);
            } catch (ValidationException $exception) {
                $errors["records.{$index}"] = $exception->errors();
            }
        }

        if ($created === []) {
            return response()->json(['message' => 'No DNS records were created.', 'errors' => $errors], 422);
        }

        return response()->json(['data' => $created, 'errors' => $errors], $errors === [] ? 201 : 207);
    }

    public function check(Request $request, RecordDnsCheck $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['zone_id' => ['nullable', 'uuid'], 'kind' => ['nullable', 'in:validation,propagation,dnssec'], 'status' => ['nullable', 'in:pending,passed,failed'], 'result' => ['nullable', 'array']]);
        $item = $record->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-dns-check', 'attributes' => $item->only(['zone_id', 'kind', 'status', 'result', 'checked_at'])]], 201);
    }

    public function resolutionCheck(Request $request, string $zone, CheckDnsResolution $check): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['record_id' => ['nullable', 'uuid'], 'record_type' => ['nullable', 'in:A,AAAA,CNAME,MX,TXT,NS,PTR,SRV,CAA'], 'resolver' => ['nullable', 'string', 'max:255']]);
        $result = $check->execute(array_merge($data, ['team_id' => $teamId, 'zone_id' => $zone]));

        return response()->json(['success' => $result['success'], 'data' => ['id' => $result['validation']->getKey(), 'type' => 'control-panel-dns-validation', 'attributes' => $result['validation']->only(['zone_id', 'record_id', 'status', 'resolver', 'expected', 'observed', 'checked_at', 'details'])]]);
    }

    public function propagationCheck(Request $request, string $zone, CheckDnsPropagation $check): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['record_id' => ['nullable', 'uuid']]);
        $result = $check->execute(array_merge($data, ['team_id' => $teamId, 'zone_id' => $zone]));

        return response()->json(['success' => $result['success'], 'data' => ['id' => $result['propagation']->getKey(), 'type' => 'control-panel-dns-propagation', 'attributes' => $result['propagation']->only(['zone_id', 'record_id', 'status', 'nameservers', 'results', 'checked_at'])]]);
    }

    public function feature(Request $request, RegisterDnsFeature $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['kind' => ['required', 'in:template,dnssec,provider,validation,propagation'], 'payload' => ['required', 'array']]);
        $item = $register->execute(array_merge($data['payload'], ['kind' => $data['kind'], 'team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-dns-'.$data['kind'], 'attributes' => $item->only(self::FEATURE_FIELDS[$data['kind']])]], 201);
    }

    public function suspend(Request $request, string $zone, SuspendZone $suspend): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = Zone::query()->whereKey($zone)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => self::resource($suspend->execute($item))]);
    }

    public function archive(Request $request, string $zone, ArchiveZone $archive): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = Zone::query()->whereKey($zone)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => self::resource($archive->execute($item))]);
    }

    private static function resource(Zone $zone): array
    {
        return ['id' => $zone->getKey(), 'type' => 'control-panel-dns-zone', 'attributes' => $zone->only(['domain', 'status', 'provider', 'dnssec_enabled', 'metadata'])];
    }

    private static function recordResource(Record $record): array
    {
        return ['id' => $record->getKey(), 'type' => 'control-panel-dns-record', 'attributes' => $record->only(['zone_id', 'name', 'type', 'content', 'ttl', 'priority', 'metadata'])];
    }
}
