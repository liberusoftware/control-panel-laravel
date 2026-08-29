<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Containers\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Containers\Models\ContainerImage;
use Liberu\ControlPanel\Containers\Models\ContainerLifecycle;
use Liberu\ControlPanel\Containers\Models\ContainerLimit;
use Liberu\ControlPanel\Containers\Models\ContainerNetwork;
use Liberu\ControlPanel\Containers\Models\ContainerRegistry;
use Liberu\ControlPanel\Containers\Models\ContainerSecret;
use Liberu\ControlPanel\Containers\Models\ContainerVolume;
use Liberu\ControlPanel\Containers\Models\Workload;

final class RegisterContainerAsset
{
    public function execute(array $attributes): Model
    {
        $teamId = trim((string) ($attributes['team_id'] ?? ''));
        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A team is required.']);
        }

        $kind = (string) ($attributes['kind'] ?? '');
        $map = [
            'image' => ContainerImage::class,
            'registry' => ContainerRegistry::class,
            'network' => ContainerNetwork::class,
            'volume' => ContainerVolume::class,
            'secret' => ContainerSecret::class,
            'limit' => ContainerLimit::class,
            'lifecycle' => ContainerLifecycle::class,
        ];
        if (! isset($map[$kind])) {
            throw ValidationException::withMessages(['kind' => 'Unsupported container asset.']);
        }
        $attributes['id'] = $attributes['id'] ?? (string) Str::uuid();
        $attributes['team_id'] = $teamId;
        if (isset($attributes['workload_id']) && ! Workload::query()->whereKey($attributes['workload_id'])->where('team_id', $teamId)->exists()) {
            abort(404);
        }
        unset($attributes['kind']);
        $defaults = match ($kind) {
            'network' => ['status' => 'active'],
            'volume' => ['status' => 'available'],
            'secret' => ['active' => true],
            'lifecycle' => ['status' => 'queued', 'requested_at' => now()],
            default => [],
        };
        $attributes = array_merge($defaults, $attributes);

        return $map[$kind]::query()->create($attributes);
    }
}
