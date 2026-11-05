<?php

namespace App\Console\Commands;

use App\Hosting\Kubernetes\KubernetesProvisioner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Liberu\ControlPanel\ControlCore\Actions\WriteAuditEntry;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\Databases\DatabasesServiceProvider;
use Liberu\ControlPanel\Databases\Enums\DatabaseStatus;
use Liberu\ControlPanel\Databases\Models\Database;
use Liberu\ControlPanel\Databases\Models\DatabaseEngine;
use Liberu\ControlPanel\Kubernetes\KubernetesServiceProvider;
use Liberu\ControlPanel\Kubernetes\Models\Cluster;
use Throwable;

#[Signature('hosting:deploy-data-cluster {source : Operator-owned database source alias} {--dry-run : Check the operator API and resource ownership without changing resources}')]
#[Description('Provision persistent PostgreSQL, MariaDB, Redis or Valkey clusters on an allowlisted Kubernetes cluster')]
class DeployDataCluster extends Command
{
    public function handle(KubernetesProvisioner $provisioner, WriteAuditEntry $audit): int
    {
        foreach ([KubernetesServiceProvider::class, DatabasesServiceProvider::class, ControlCoreServiceProvider::class] as $provider) {
            if (! app()->providerIsLoaded($provider)) {
                $this->error('Enable the Kubernetes, databases and control-core modules before provisioning database clusters.');

                return self::FAILURE;
            }
        }
        $source = config('hosting-kubernetes.data_sources.'.(string) $this->argument('source'));
        if (! is_array($source) || ! filled($source['team_id'] ?? null) || ! filled($source['database_id'] ?? null)
            || ! filled($source['cluster_id'] ?? null) || ! is_string($source['connection'] ?? null) || ! is_array($source['settings'] ?? null)) {
            $this->error('Configure an operator-owned team, database, cluster, connection and settings.');

            return self::FAILURE;
        }
        $database = Database::query()->whereKey($source['database_id'])->where('team_id', $source['team_id'])->first();
        $engine = $database === null ? null : DatabaseEngine::query()->whereKey($database->getAttribute('engine_id'))->where('team_id', $source['team_id'])->where('active', true)->first();
        $cluster = Cluster::query()->whereKey($source['cluster_id'])->where('team_id', $source['team_id'])->whereIn('status', ['pending', 'active'])->first();
        $connection = config('hosting-kubernetes.connections.'.$source['connection']);
        if ($database === null || $engine === null || $cluster === null || ! is_array($connection)
            || ! in_array($database->getAttribute('status'), [DatabaseStatus::Provisioning, DatabaseStatus::Active], true)
            || rtrim((string) $cluster->getAttribute('endpoint'), '/') !== rtrim((string) ($connection['url'] ?? ''), '/')) {
            $this->error('Database and cluster ownership, lifecycle or endpoint does not match the operator allowlist.');

            return self::FAILURE;
        }
        if (config('cache.default') !== 'redis') {
            $this->error('Database cluster provisioning requires shared Redis locking.');

            return self::FAILURE;
        }
        $lock = null;
        try {
            $lock = Cache::lock('hosting:data-cluster:'.hash('sha256', $cluster->getKey().':'.$database->getKey()), 600);
            if (! $lock->get()) {
                $this->error('This database cluster is already being provisioned.');

                return self::FAILURE;
            }
            $driver = $engine->getAttribute('driver') === 'pgsql' ? 'postgresql' : $engine->getAttribute('driver');
            $result = $provisioner->deployDataCluster((string) $database->getAttribute('team_id'), (string) $database->getKey(), $driver, [...$source['settings'], 'database' => $database->getAttribute('name')], $connection, (bool) $this->option('dry-run'));
            $fresh = $database->fresh();
            $freshEngine = $engine->fresh();
            $freshCluster = $cluster->fresh();
            $unchanged = $fresh !== null && $fresh->getAttribute('team_id') === $database->getAttribute('team_id') && $fresh->getAttribute('engine_id') === $database->getAttribute('engine_id') && $fresh->getAttribute('name') === $database->getAttribute('name') && $fresh->getAttribute('status') === $database->getAttribute('status')
                && $freshEngine !== null && Arr::only($freshEngine->getAttributes(), ['team_id', 'driver', 'version', 'host', 'port', 'active']) === Arr::only($engine->getAttributes(), ['team_id', 'driver', 'version', 'host', 'port', 'active'])
                && $freshCluster !== null && Arr::only($freshCluster->getAttributes(), ['team_id', 'endpoint', 'status']) === Arr::only($cluster->getAttributes(), ['team_id', 'endpoint', 'status']);
            $result['ready'] = $result['ready'] && $unchanged;
            $result['inventory_unchanged'] = $unchanged;
            $audit->execute('hosting.data-cluster.provisioned', (string) $database->getAttribute('team_id'), null, 'database', (string) $database->getKey(), $result);
            $this->table(['Kind', 'Name', 'Result'], array_map(fn (array $resource): array => [$resource['kind'], $resource['name'], $resource['status']], $result['resources']));
            if ($result['dry_run'] && $unchanged) {
                $this->info('Operator discovery and ownership preflight passed. No resources were changed.');

                return self::SUCCESS;
            }
            if (! $result['ready']) {
                $this->error('Database resources were applied but cluster readiness is not verified. Inspect the operator and rerun.');

                return self::FAILURE;
            }
            $this->info(ucfirst($driver).' cluster reports ready in '.$result['namespace'].'. Validate data-plane failover separately.');

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable) {
            $this->recordFailure($audit, (string) $database->getAttribute('team_id'), 'database', (string) $database->getKey(), 'hosting.data-cluster.failed');
            $this->error('Database cluster provisioning failed. Inspect operator availability, ownership, credentials and partial state. Persistent volumes were not deleted.');

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
