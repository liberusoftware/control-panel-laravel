<?php

declare(strict_types=1);
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Kubernetes\Actions\RegisterCluster;
use Liberu\ControlPanel\Kubernetes\Actions\RegisterKubernetesAsset;
use Liberu\ControlPanel\Kubernetes\KubernetesServiceProvider;

uses(RefreshDatabase::class);
beforeEach(function (): void {
    app()->register(KubernetesServiceProvider::class);
    $this->artisan('migrate');
});
it('supports cluster resources across the Kubernetes lifecycle', function (): void {
    $a = app(RegisterKubernetesAsset::class);
    $cluster = app(RegisterCluster::class)->execute(['team_id' => 'team-1', 'name' => 'Primary', 'endpoint' => 'https://k8s.example.test']);
    $node = $a->execute(['team_id' => 'team-1', 'kind' => 'node', 'cluster_id' => $cluster->getKey(), 'name' => 'worker-1', 'status' => 'Ready', 'schedulable' => true]);
    $namespace = $a->execute(['team_id' => 'team-1', 'kind' => 'namespace', 'cluster_id' => $cluster->getKey(), 'name' => 'production']);
    $rbac = $a->execute(['team_id' => 'team-1', 'kind' => 'rbac', 'name' => 'deployer', 'role' => 'admin', 'subjects' => ['account-1']]);
    $workload = $a->execute(['team_id' => 'team-1', 'kind' => 'workload', 'name' => 'web', 'workload_kind' => 'Deployment', 'image' => 'nginx']);
    $ingress = $a->execute(['team_id' => 'team-1', 'kind' => 'ingress', 'name' => 'web', 'host' => 'example.test', 'paths' => ['/']]);
    $helm = $a->execute(['team_id' => 'team-1', 'kind' => 'helm', 'namespace' => 'production', 'name' => 'app', 'chart' => 'app-chart']);
    $storage = $a->execute(['team_id' => 'team-1', 'kind' => 'storage', 'name' => 'data', 'access_modes' => ['ReadWriteOnce']]);
    $autoscaler = $a->execute(['team_id' => 'team-1', 'kind' => 'autoscaling', 'name' => 'web', 'target' => 'deployment/web', 'min_replicas' => 1, 'max_replicas' => 5, 'metric' => 'cpu']);
    $upgrade = $a->execute(['team_id' => 'team-1', 'kind' => 'upgrade', 'from_version' => '1.28', 'to_version' => '1.29']);
    $view = $a->execute(['team_id' => 'team-1', 'kind' => 'cluster-view', 'name' => 'all', 'cluster_ids' => [$cluster->getKey()]]);
    expect($node->isSchedulable())->toBeTrue()->and($namespace->name)->toBe('production')->and($rbac->role)->toBe('admin')->and($workload->image)->toBe('nginx')->and($ingress->host)->toBe('example.test')->and($helm->chart)->toBe('app-chart')->and($storage->access_modes)->toContain('ReadWriteOnce')->and($autoscaler->max_replicas)->toBe(5)->and($upgrade->to_version)->toBe('1.29')->and($view->cluster_ids)->toContain($cluster->getKey());
});
it('rejects unknown Kubernetes assets', function (): void {
    expect(fn () => app(RegisterKubernetesAsset::class)->execute(['team_id' => 'team-1', 'kind' => 'unknown']))->toThrow(ValidationException::class);
});
