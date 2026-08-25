<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Containers\Actions;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
final class RegisterContainerAsset
{
    public function execute(array $attributes): Model
    {
        $kind = (string) ($attributes['kind'] ?? '');
        $map = [
            'image' => \Liberu\ControlPanel\Containers\Models\ContainerImage::class,
            'registry' => \Liberu\ControlPanel\Containers\Models\ContainerRegistry::class,
            'network' => \Liberu\ControlPanel\Containers\Models\ContainerNetwork::class,
            'volume' => \Liberu\ControlPanel\Containers\Models\ContainerVolume::class,
            'secret' => \Liberu\ControlPanel\Containers\Models\ContainerSecret::class,
            'limit' => \Liberu\ControlPanel\Containers\Models\ContainerLimit::class,
            'lifecycle' => \Liberu\ControlPanel\Containers\Models\ContainerLifecycle::class,
        ];
        if (! isset($map[$kind])) throw ValidationException::withMessages(['kind' => 'Unsupported container asset.']);
        $attributes['id'] = $attributes['id'] ?? (string) Str::uuid();
        $attributes['team_id'] = $attributes['team_id'] ?? null;
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
