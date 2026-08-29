<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Containers\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Containers\Models\ContainerResource;
use Liberu\ControlPanel\Containers\Models\Workload;

final class RecordContainerResource
{
    public function execute(array $a): ContainerResource
    {
        $teamId = trim((string) ($a['team_id'] ?? ''));
        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A team is required.']);
        }

        $kind = (string) ($a['kind'] ?? '');
        if (! in_array($kind, ['image', 'registry', 'network', 'volume', 'secret', 'limit', 'lifecycle'], true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported container resource.']);
        }

        $workloadId = $a['workload_id'] ?? null;
        if ($workloadId !== null && ! Workload::query()->whereKey($workloadId)->where('team_id', $teamId)->exists()) {
            abort(404);
        }

        return ContainerResource::query()->create(['id' => (string) Str::uuid(), 'team_id' => $teamId, 'workload_id' => $workloadId, 'kind' => $kind, 'name' => trim((string) ($a['name'] ?? '')), 'status' => $a['status'] ?? 'active', 'spec' => $a['spec'] ?? []]);
    }
}
