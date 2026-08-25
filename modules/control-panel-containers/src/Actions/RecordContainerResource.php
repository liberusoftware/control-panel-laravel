<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Containers\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Containers\Models\ContainerResource;

final class RecordContainerResource
{
    public function execute(array $a): ContainerResource
    {
        $kind = (string) ($a['kind'] ?? '');
        if (! in_array($kind, ['image', 'registry', 'network', 'volume', 'secret', 'limit', 'lifecycle'], true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported container resource.']);
        }

        return ContainerResource::query()->create(['id' => (string) Str::uuid(), 'team_id' => $a['team_id'] ?? null, 'workload_id' => $a['workload_id'] ?? null, 'kind' => $kind, 'name' => trim((string) ($a['name'] ?? '')), 'status' => $a['status'] ?? 'active', 'spec' => $a['spec'] ?? []]);
    }
}
