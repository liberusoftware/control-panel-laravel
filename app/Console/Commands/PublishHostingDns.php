<?php

namespace App\Console\Commands;

use App\Hosting\Dns\PowerDnsCluster;
use App\Hosting\Dns\PowerDnsZone;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Liberu\ControlPanel\Dns\Actions\RecordDnsCheck;
use Liberu\ControlPanel\Dns\DnsServiceProvider;
use Liberu\ControlPanel\Dns\Enums\ZoneStatus;
use Liberu\ControlPanel\Dns\Models\Zone;
use Throwable;

#[Signature('hosting:publish-dns {source : Operator-configured DNS source alias} {--dry-run : Check every node without changing DNS}')]
#[Description('Publish an allowlisted zone to PowerDNS nodes and verify API read-back on every node')]
class PublishHostingDns extends Command
{
    public function handle(PowerDnsCluster $cluster, PowerDnsZone $names, RecordDnsCheck $checks): int
    {
        if (! app()->providerIsLoaded(DnsServiceProvider::class)) {
            $this->error('Enable the control-panel-dns module before publishing DNS.');

            return self::FAILURE;
        }
        $source = config('hosting-dns.sources.'.(string) $this->argument('source'));
        if (! is_array($source) || ! filled($source['team_id'] ?? null) || ! filled($source['zone_id'] ?? null)
            || ! filled($source['domain'] ?? null) || ! is_string($source['cluster'] ?? null)) {
            $this->error('Configure an operator-owned DNS source with team, zone, domain and cluster.');

            return self::FAILURE;
        }
        $zone = Zone::query()->whereKey($source['zone_id'])->where('team_id', $source['team_id'])->first();
        if ($zone === null || $zone->dnssec_enabled || ! in_array($zone->status, [ZoneStatus::Draft, ZoneStatus::Active], true)) {
            $this->error('An unsigned draft or active zone belonging to the configured team is required.');

            return self::FAILURE;
        }
        if (config('cache.default') !== 'redis') {
            $this->error('DNS publication requires shared Redis locking. Configure CACHE_STORE=redis.');

            return self::FAILURE;
        }
        $lock = null;
        try {
            if ($names->domain($zone->domain) !== $names->domain($source['domain'])) {
                $this->error('The inventory domain does not match the operator allowlist.');

                return self::FAILURE;
            }
            $owner = 'liberu-'.substr(hash('sha256', $zone->team_id.':'.$zone->getKey()), 0, 32);
            $lock = Cache::lock('hosting:dns:'.hash('sha256', $names->domain($zone->domain)), 600);
            if (! $lock->get()) {
                $this->error('This zone is already being published. Retry after the running publication finishes.');

                return self::FAILURE;
            }
            $records = $this->records($zone);
            $dryRun = (bool) $this->option('dry-run');
            $result = $cluster->synchronize($zone->domain, $owner, $records, config('hosting-dns.clusters.'.$source['cluster'], []), $dryRun);
            $fresh = $zone->fresh();
            $unchanged = $fresh !== null && $fresh->domain === $zone->domain && $fresh->team_id === $zone->team_id
                && $fresh->status === $zone->status && $fresh->dnssec_enabled === $zone->dnssec_enabled
                && $this->records($fresh) === $records;
            $result['verified'] = $result['verified'] && $unchanged;
            $result['inventory_unchanged'] = $unchanged;
            $ready = $unchanged && count(array_filter($result['nodes'], fn (array $node): bool => str_starts_with($node['status'], 'ready-'))) === count($result['nodes']);
            $result['driver'] = 'powerdns';
            $result['operation'] = $dryRun ? 'preflight' : 'publication';
            $checks->execute([
                'team_id' => $zone->team_id, 'zone_id' => $zone->getKey(),
                'kind' => 'validation',
                'status' => ($dryRun ? $ready : $result['verified']) ? 'passed' : 'failed',
                'result' => $result,
            ]);
            $this->table(['Node', 'Result'], collect($result['nodes'])->map(fn (array $node, string $alias): array => [$alias, $node['status']])->values()->all());
            if ($dryRun && $ready) {
                $this->info('Preflight passed. No DNS records were changed.');

                return self::SUCCESS;
            }
            if (! $result['verified']) {
                $this->error('Publication is not verified on every node, or inventory changed. Inspect node results and rerun to reconcile.');

                return self::FAILURE;
            }
            $this->info('DNS API records verified on every configured node. Check delegation and live DNS responses separately.');

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable) {
            $this->error('DNS publication failed. Check the allowlist, record validation, Redis and node connectivity. No cluster-wide success was recorded.');

            return self::FAILURE;
        } finally {
            $lock?->release();
        }
    }

    /** @return list<array<string, mixed>> */
    private function records(Zone $zone): array
    {
        return $zone->records()->orderBy('id')->get()->map->only(['name', 'type', 'content', 'ttl', 'priority'])->all();
    }
}
