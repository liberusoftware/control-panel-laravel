<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Kubernetes\Models\Cluster;
use Liberu\ControlPanel\Kubernetes\Models\HelmRelease;

final class UpdateHelmRelease
{
    /** @param array<string, mixed> $attributes */
    public function execute(HelmRelease $release, array $attributes): HelmRelease
    {
        $name = trim((string) ($attributes['name'] ?? $release->name));
        $namespace = trim((string) ($attributes['namespace'] ?? $release->namespace));
        $chart = trim((string) ($attributes['chart'] ?? $release->chart));

        if ($name === '' || $namespace === '' || $chart === '') {
            throw ValidationException::withMessages(['release' => 'A release name, namespace, and chart are required.']);
        }

        $clusterId = array_key_exists('cluster_id', $attributes) ? ($attributes['cluster_id'] ?: null) : $release->cluster_id;
        if ($clusterId !== null && ! Cluster::query()->whereKey($clusterId)->where('team_id', $release->team_id)->exists()) {
            throw ValidationException::withMessages(['cluster_id' => 'The cluster is not available in the release team.']);
        }

        if (array_key_exists('values', $attributes) && ! is_array($attributes['values'])) {
            throw ValidationException::withMessages(['values' => 'Helm values must be an object.']);
        }

        if (array_key_exists('status', $attributes) && ! in_array($attributes['status'], ['pending', 'deployed', 'failed', 'uninstalled'], true)) {
            throw ValidationException::withMessages(['status' => 'The Helm release status is invalid.']);
        }

        $release->forceFill([
            'cluster_id' => $clusterId,
            'namespace' => $namespace,
            'name' => $name,
            'chart' => $chart,
            'version' => array_key_exists('version', $attributes) ? $attributes['version'] : $release->version,
            'values' => array_key_exists('values', $attributes) ? $attributes['values'] : $release->values,
            'status' => $attributes['status'] ?? $release->status,
        ])->save();

        return $release->refresh();
    }
}
