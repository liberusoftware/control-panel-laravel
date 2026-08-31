<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class KubernetesNode extends Model
{
    use HasUuids;

    protected $table = 'control_panel_kubernetes_nodes';

    protected $fillable = ['id', 'team_id', 'cluster_id', 'name', 'uid', 'kubernetes_version', 'container_runtime', 'os_image', 'kernel_version', 'architecture', 'status', 'status_message', 'schedulable', 'labels', 'annotations', 'taints', 'addresses', 'capacity', 'allocatable', 'conditions', 'last_heartbeat_at'];

    protected function casts(): array
    {
        return ['schedulable' => 'boolean', 'labels' => 'array', 'annotations' => 'array', 'taints' => 'array', 'addresses' => 'array', 'capacity' => 'array', 'allocatable' => 'array', 'conditions' => 'array', 'last_heartbeat_at' => 'datetime'];
    }

    public function isReady(): bool
    {
        return $this->status === 'Ready';
    }

    public function isSchedulable(): bool
    {
        return $this->schedulable && $this->isReady();
    }

    public function hasLabel(string $key, ?string $value = null): bool
    {
        $labels = $this->labels ?? [];

        return array_key_exists($key, $labels) && ($value === null || (string) $labels[$key] === $value);
    }

    public function getRole(): string
    {
        return $this->hasLabel('node-role.kubernetes.io/master') || $this->hasLabel('node-role.kubernetes.io/control-plane')
            ? 'master'
            : 'worker';
    }

    public function getCpuCapacity(): ?float
    {
        return $this->resourceQuantity($this->capacity['cpu'] ?? null);
    }

    public function getAllocatableCpu(): ?float
    {
        return $this->resourceQuantity($this->allocatable['cpu'] ?? null);
    }

    public function getMemoryCapacity(): ?float
    {
        return $this->memoryQuantity($this->capacity['memory'] ?? null);
    }

    public function getAllocatableMemory(): ?float
    {
        return $this->memoryQuantity($this->allocatable['memory'] ?? null);
    }

    public function scopeReady(Builder $query): Builder
    {
        return $query->where('status', 'Ready');
    }

    public function scopeSchedulable(Builder $query): Builder
    {
        return $query->where('schedulable', true)->where('status', 'Ready');
    }

    private function resourceQuantity(mixed $quantity): ?float
    {
        if (! is_string($quantity) && ! is_numeric($quantity)) {
            return null;
        }

        $quantity = (string) $quantity;

        return str_ends_with($quantity, 'm') ? (float) substr($quantity, 0, -1) / 1000 : (float) $quantity;
    }

    private function memoryQuantity(mixed $quantity): ?float
    {
        if (! is_string($quantity) && ! is_numeric($quantity)) {
            return null;
        }

        $quantity = (string) $quantity;
        $units = ['Ki' => 1024, 'Mi' => 1024 ** 2, 'Gi' => 1024 ** 3, 'Ti' => 1024 ** 4, 'K' => 1000, 'M' => 1000 ** 2, 'G' => 1000 ** 3, 'T' => 1000 ** 4];

        foreach ($units as $unit => $multiplier) {
            if (str_ends_with($quantity, $unit)) {
                return (float) substr($quantity, 0, -strlen($unit)) * $multiplier / (1024 ** 3);
            }
        }

        return (float) $quantity / (1024 ** 3);
    }
}
