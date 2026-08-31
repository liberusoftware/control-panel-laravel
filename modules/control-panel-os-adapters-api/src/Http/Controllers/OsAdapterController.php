<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\OsAdapters\Actions\CreateFirewallRule;
use Liberu\ControlPanel\OsAdapters\Actions\DeleteFirewallRule;
use Liberu\ControlPanel\OsAdapters\Actions\RecordOsResource;
use Liberu\ControlPanel\OsAdapters\Actions\RecordSupportMatrix;
use Liberu\ControlPanel\OsAdapters\Actions\RegisterOsAdapter;
use Liberu\ControlPanel\OsAdapters\Actions\UpdateFirewallRule;
use Liberu\ControlPanel\OsAdapters\Actions\UpdateOsService;
use Liberu\ControlPanel\OsAdapters\Models\FilesystemMount;
use Liberu\ControlPanel\OsAdapters\Models\FirewallRule;
use Liberu\ControlPanel\OsAdapters\Models\OsAdapter;
use Liberu\ControlPanel\OsAdapters\Models\OsPackage;
use Liberu\ControlPanel\OsAdapters\Models\OsService;
use Liberu\ControlPanel\OsAdapters\Models\OsUser;
use Liberu\ControlPanel\OsAdapters\Models\PackageRepository;
use Liberu\ControlPanel\OsAdapters\Queries\InstallationCommandReport;
use Liberu\ControlPanel\OsAdapters\Queries\ListOsAdapters;
use Liberu\ControlPanel\OsAdapters\Queries\ServiceStatusReport;

final class OsAdapterController
{
    /** @var array<class-string<Model>, list<string>> */
    private const RESOURCE_FIELDS = [
        OsPackage::class => ['node_id', 'name', 'version', 'architecture', 'status', 'metadata'],
        OsService::class => ['node_id', 'name', 'version', 'status', 'enabled', 'metadata'],
        OsUser::class => ['node_id', 'username', 'uid', 'shell', 'home', 'sudo', 'status', 'metadata'],
        FilesystemMount::class => ['node_id', 'device', 'mount_path', 'filesystem', 'size_bytes', 'free_bytes', 'options', 'mounted'],
        PackageRepository::class => ['node_id', 'name', 'url', 'distribution', 'enabled', 'trusted', 'metadata'],
    ];

    public function index(Request $request, ListOsAdapters $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $items = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $items->through(static fn (OsAdapter $item): array => self::resource($item)), 'meta' => ['current_page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = OsAdapter::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => self::resource($item)]);
    }

    public function serviceStatuses(Request $request, ServiceStatusReport $report): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return response()->json(['services' => $report->all($teamId)->map(fn (OsService $service): array => $this->serviceResource($service))->values()]);
    }

    public function missingServices(Request $request, ServiceStatusReport $report): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $services = $report->missing($teamId);

        return response()->json(['missing_services' => $services->map(fn (OsService $service): array => $this->serviceResource($service))->values(), 'count' => $services->count()]);
    }

    public function stoppedServices(Request $request, ServiceStatusReport $report): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $services = $report->stopped($teamId);

        return response()->json(['stopped_services' => $services->map(fn (OsService $service): array => $this->serviceResource($service))->values(), 'count' => $services->count()]);
    }

    public function installationCommands(Request $request, InstallationCommandReport $report): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $result = $report->execute($teamId);

        return response()->json($result + ['count' => count($result['commands'])]);
    }

    public function checkService(Request $request, string $service, ServiceStatusReport $report): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = $report->find($teamId, $service);
        abort_if($item === null, 404, 'Service not found.');

        return response()->json(['data' => $this->serviceResource($item)]);
    }

    public function store(Request $request, RegisterOsAdapter $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['node_id' => ['required', 'uuid'], 'operating_system' => ['required', 'string', 'max:80'], 'version' => ['required', 'string', 'max:80'], 'capabilities' => ['nullable', 'array'], 'metadata' => ['nullable', 'array']]);
        $item = $register->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($item)], 201);
    }

    public function package(Request $request, RecordOsResource $record): JsonResponse
    {
        return $this->record($request, $record, OsPackage::class, ['name' => ['required', 'string', 'max:160'], 'version' => ['nullable', 'string', 'max:80'], 'architecture' => ['nullable', 'string', 'max:40'], 'status' => ['required', 'string', 'max:40'], 'metadata' => ['nullable', 'array']]);
    }

    public function service(Request $request, RecordOsResource $record): JsonResponse
    {
        return $this->record($request, $record, OsService::class, ['name' => ['required', 'string', 'max:160'], 'version' => ['nullable', 'string', 'max:80'], 'status' => ['required', 'string', 'max:40'], 'enabled' => ['sometimes', 'boolean'], 'metadata' => ['nullable', 'array']]);
    }

    public function updateService(Request $request, string $service, UpdateOsService $update): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = OsService::query()->whereKey($service)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:160'], 'version' => ['sometimes', 'nullable', 'string', 'max:80'], 'status' => ['sometimes', 'string', 'max:40'], 'enabled' => ['sometimes', 'boolean'], 'metadata' => ['sometimes', 'array']]);
        $item = $update->execute($item, $data);

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-os-service', 'attributes' => $item->only(['node_id', 'name', 'version', 'status', 'enabled', 'metadata'])]]);
    }

    public function firewall(Request $request, CreateFirewallRule $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['node_id' => ['required', 'uuid'], 'direction' => ['required', 'in:inbound,outbound'], 'action' => ['required', 'in:allow,deny,reject'], 'protocol' => ['nullable', 'string', 'max:20'], 'port' => ['nullable', 'integer', 'between:1,65535'], 'source' => ['nullable', 'string', 'max:64'], 'comment' => ['nullable', 'string', 'max:255'], 'active' => ['sometimes', 'boolean']]);

        return response()->json(['data' => $this->firewallResource($create->execute(array_merge($data, ['team_id' => $teamId])))], 201);
    }

    public function updateFirewall(Request $request, string $rule, UpdateFirewallRule $update): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = FirewallRule::query()->whereKey($rule)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate(['direction' => ['sometimes', 'in:inbound,outbound'], 'action' => ['sometimes', 'in:allow,deny,reject'], 'protocol' => ['sometimes', 'nullable', 'string', 'max:20'], 'port' => ['sometimes', 'nullable', 'integer', 'between:1,65535'], 'source' => ['sometimes', 'nullable', 'string', 'max:64'], 'comment' => ['sometimes', 'nullable', 'string', 'max:255'], 'active' => ['sometimes', 'boolean']]);

        return response()->json(['data' => $this->firewallResource($update->execute($item, $data))]);
    }

    public function deleteFirewall(Request $request, string $rule, DeleteFirewallRule $delete): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = FirewallRule::query()->whereKey($rule)->where('team_id', $teamId)->firstOrFail();
        $delete->execute($item);

        return response()->json(status: 204);
    }

    public function user(Request $request, RecordOsResource $record): JsonResponse
    {
        return $this->record($request, $record, OsUser::class, ['username' => ['required', 'string', 'max:80'], 'uid' => ['nullable', 'integer', 'min:0'], 'shell' => ['nullable', 'string', 'max:255'], 'home' => ['nullable', 'string', 'max:1024'], 'sudo' => ['sometimes', 'boolean'], 'status' => ['required', 'string', 'max:40'], 'metadata' => ['nullable', 'array']]);
    }

    public function filesystem(Request $request, RecordOsResource $record): JsonResponse
    {
        return $this->record($request, $record, FilesystemMount::class, ['device' => ['required', 'string', 'max:255'], 'mount_path' => ['required', 'string', 'max:1024'], 'filesystem' => ['nullable', 'string', 'max:80'], 'size_bytes' => ['nullable', 'integer', 'min:0'], 'free_bytes' => ['nullable', 'integer', 'min:0'], 'options' => ['nullable', 'array'], 'mounted' => ['sometimes', 'boolean']]);
    }

    public function repository(Request $request, RecordOsResource $record): JsonResponse
    {
        return $this->record($request, $record, PackageRepository::class, ['name' => ['required', 'string', 'max:160'], 'url' => ['required', 'url', 'max:2048'], 'distribution' => ['nullable', 'string', 'max:80'], 'enabled' => ['sometimes', 'boolean'], 'trusted' => ['sometimes', 'boolean'], 'metadata' => ['nullable', 'array']]);
    }

    public function supportMatrix(Request $request, RecordSupportMatrix $record): JsonResponse
    {
        $data = $request->validate(['operating_system' => ['required', 'string', 'max:80'], 'version' => ['required', 'string', 'max:80'], 'capability' => ['required', 'string', 'max:120'], 'supported' => ['required', 'boolean'], 'minimum_adapter_version' => ['nullable', 'string', 'max:80'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $item = $record->execute($data);

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-os-support-matrix', 'attributes' => $item->only(['operating_system', 'version', 'capability', 'supported', 'minimum_adapter_version', 'notes'])]], 201);
    }

    /** @param array<string, array<int, mixed>> $rules */
    private function record(Request $request, RecordOsResource $record, string $model, array $rules): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(array_merge(['node_id' => ['required', 'uuid']], $rules));
        $item = $record->execute($model, array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-os-resource', 'attributes' => $item->only(self::RESOURCE_FIELDS[$model])]], 201);
    }

    private static function resource(OsAdapter $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-os-adapter', 'attributes' => $item->only(['node_id', 'operating_system', 'version', 'capabilities', 'status', 'metadata'])];
    }

    private function serviceResource(OsService $service): array
    {
        return ['id' => $service->getKey(), 'type' => 'control-panel-os-service', 'attributes' => $service->only(['node_id', 'name', 'version', 'status', 'enabled', 'metadata'])];
    }

    /** @return array<string, mixed> */
    private function firewallResource(FirewallRule $rule): array
    {
        return ['id' => $rule->getKey(), 'type' => 'control-panel-firewall-rule', 'attributes' => $rule->only(['node_id', 'direction', 'action', 'protocol', 'port', 'source', 'comment', 'active'])];
    }
}
