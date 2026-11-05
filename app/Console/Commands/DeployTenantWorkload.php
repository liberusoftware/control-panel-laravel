<?php

namespace App\Console\Commands;

use App\Hosting\Kubernetes\KubernetesProvisioner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Liberu\ControlPanel\ControlCore\Actions\WriteAuditEntry;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\Kubernetes\KubernetesServiceProvider;
use Liberu\ControlPanel\Kubernetes\Models\Cluster;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesWorkload;
use Throwable;

#[Signature('hosting:deploy-workload {source : Operator-owned workload source alias} {--dry-run : Validate the plan and remote ownership without changing resources}')]
#[Description('Apply a restricted, sandboxed tenant workload to an allowlisted Kubernetes cluster')]
class DeployTenantWorkload extends Command
{
    public function handle(KubernetesProvisioner $provisioner, WriteAuditEntry $audit): int
    {
        if (! app()->providerIsLoaded(KubernetesServiceProvider::class) || ! app()->providerIsLoaded(ControlCoreServiceProvider::class)) {
            $this->error('Enable the Kubernetes and control-core modules before provisioning workloads.');

            return self::FAILURE;
        }
        $source = config('hosting-kubernetes.sources.'.(string) $this->argument('source'));
        if (! is_array($source) || ! filled($source['team_id'] ?? null) || ! filled($source['cluster_id'] ?? null)
            || ! filled($source['workload_id'] ?? null) || ! is_string($source['connection'] ?? null)
            || ! is_array($source['settings'] ?? null)) {
            $this->error('Configure an operator-owned team, cluster, workload, connection and image allowlist.');

            return self::FAILURE;
        }
        $cluster = Cluster::query()->whereKey($source['cluster_id'])->where('team_id', $source['team_id'])->whereIn('status', ['pending', 'active'])->first();
        $workload = KubernetesWorkload::query()->whereKey($source['workload_id'])->where('team_id', $source['team_id'])->where('cluster_id', $source['cluster_id'])->first();
        $connection = config('hosting-kubernetes.connections.'.$source['connection']);
        if ($cluster === null || $workload === null || $workload->getAttribute('kind') !== 'Deployment' || ! is_array($connection)
            || rtrim((string) $cluster->getAttribute('endpoint'), '/') !== rtrim((string) ($connection['url'] ?? ''), '/')) {
            $this->error('Workload ownership, kind or cluster endpoint does not match the operator allowlist.');

            return self::FAILURE;
        }
        if (config('cache.default') !== 'redis') {
            $this->error('Workload provisioning requires shared Redis locking.');

            return self::FAILURE;
        }
        $lock = null;
        try {
            $lock = Cache::lock('hosting:workload:'.hash('sha256', $cluster->getKey().':'.$workload->getKey()), 600);
            if (! $lock->get()) {
                $this->error('This workload is already being provisioned.');

                return self::FAILURE;
            }
            $image = $workload->getAttribute('image');
            $replicas = $workload->getAttribute('replicas');
            $result = $provisioner->deploy((string) $workload->getAttribute('team_id'), (string) $workload->getKey(), $image, [...$source['settings'], 'replicas' => $replicas], $connection, (bool) $this->option('dry-run'));
            $fresh = $workload->fresh();
            $freshCluster = $cluster->fresh();
            $unchanged = $fresh !== null && $fresh->getAttribute('team_id') === $workload->getAttribute('team_id') && $fresh->getAttribute('cluster_id') === $workload->getAttribute('cluster_id') && $fresh->getAttribute('image') === $image && $fresh->getAttribute('kind') === $workload->getAttribute('kind')
                && $fresh->getAttribute('replicas') === $replicas && $freshCluster !== null
                && $freshCluster->getAttribute('team_id') === $cluster->getAttribute('team_id')
                && $freshCluster->getAttribute('endpoint') === $cluster->getAttribute('endpoint')
                && $freshCluster->getAttribute('status') === $cluster->getAttribute('status');
            $result['ready'] = $result['ready'] && $unchanged;
            $result['inventory_unchanged'] = $unchanged;
            $audit->execute('hosting.workload.provisioned', (string) $workload->getAttribute('team_id'), null, 'kubernetes-workload', (string) $workload->getKey(), $result);
            $this->table(['Kind', 'Name', 'Result'], array_map(fn (array $resource): array => [$resource['kind'], $resource['name'], $resource['status']], $result['resources']));
            if ($result['dry_run'] && $unchanged) {
                $this->info('Plan and ownership checks passed. No resources were changed; admission and runtime isolation still require live validation.');

                return self::SUCCESS;
            }
            if (! $result['ready']) {
                $this->error('Resources were applied, but current-generation readiness is not verified. Inspect the namespace and rerun.');

                return self::FAILURE;
            }
            $this->info('Current-generation workload replicas are ready in '.$result['namespace'].'.');

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable) {
            $this->recordFailure($audit, (string) $workload->getAttribute('team_id'), 'kubernetes-workload', (string) $workload->getKey(), 'hosting.workload.failed');
            $this->error('Workload provisioning failed. Inspect ownership, API permissions, sandbox availability and partial namespace state. Existing data was not deleted.');

            return self::FAILURE;
        } finally {
            $lock?->release();
        }
    }

    private function recordFailure(WriteAuditEntry $audit, string $teamId, string $subjectType, string $subjectId, string $event): void
    {
        try {
            $audit->execute($event, $teamId, null, $subjectType, $subjectId, ['status' => 'failed', 'dry_run' => (bool) $this->option('dry-run'), 'partial_state_possible' => ! (bool) $this->option('dry-run')]);
        } catch (Throwable) {
            $this->warn('The failure could not be persisted to the audit log.');
        }
    }
}
