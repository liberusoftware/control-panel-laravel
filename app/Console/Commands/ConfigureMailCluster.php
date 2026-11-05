<?php

namespace App\Console\Commands;

use App\Hosting\Mail\StalwartCluster;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Liberu\ControlPanel\ControlCore\Actions\WriteAuditEntry;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\Mail\MailServiceProvider;
use Liberu\ControlPanel\Mail\Models\MailDomain;
use Throwable;

#[Signature('hosting:configure-mail-cluster {source : Operator-controlled mail source alias} {--dry-run : Check shared stores without changing server settings}')]
#[Description('Configure shared Redis/Valkey coordination on allowlisted Stalwart mail nodes')]
class ConfigureMailCluster extends Command
{
    public function handle(StalwartCluster $mail, WriteAuditEntry $audit): int
    {
        if (! app()->providerIsLoaded(MailServiceProvider::class) || ! app()->providerIsLoaded(ControlCoreServiceProvider::class)) {
            $this->error('Enable mail and control-core modules before configuring mail clusters.');

            return self::FAILURE;
        }
        $source = config('hosting-mail.sources.'.(string) $this->argument('source'));
        if (! is_array($source) || ! filled($source['team_id'] ?? null) || ! filled($source['domain_id'] ?? null)
            || ! filled($source['domain'] ?? null) || ! is_array($source['nodes'] ?? null)
            || ! is_array($source['data_store'] ?? null) || ! is_array($source['memory_store'] ?? null)) {
            $this->error('Configure a team-owned mail domain, allowlisted nodes, shared datastore and in-memory store.');

            return self::FAILURE;
        }
        $domain = MailDomain::query()->whereKey($source['domain_id'])->where('team_id', $source['team_id'])
            ->where('domain', $source['domain'])->whereIn('status', ['active', 'pending'])->first();
        if ($domain === null || config('cache.default') !== 'redis') {
            $this->error('An allowlisted active or pending mail domain and shared Redis locking are required.');

            return self::FAILURE;
        }
        $lock = null;
        try {
            $store = $source['data_store'];
            $key = hash('sha256', json_encode([$store['host'] ?? null, $store['port'] ?? null, $store['database'] ?? null], JSON_THROW_ON_ERROR));
            $lock = Cache::lock('hosting:mail-cluster:'.$key, 600);
            if (! $lock->get()) {
                $this->error('This mail cluster is already being configured.');

                return self::FAILURE;
            }
            $result = $mail->configure($source['nodes'], $store, $source['memory_store'], (bool) $this->option('dry-run'));
            $fresh = $domain->fresh();
            $unchanged = $fresh !== null && $fresh->getAttribute('team_id') === $domain->getAttribute('team_id') && $fresh->getAttribute('domain') === $domain->getAttribute('domain') && $fresh->getAttribute('status') === $domain->getAttribute('status');
            $result['verified'] = $result['verified'] && $unchanged;
            $result['inventory_unchanged'] = $unchanged;
            $audit->execute('hosting.mail-cluster.configured', (string) $domain->getAttribute('team_id'), null, 'mail-domain', (string) $domain->getKey(), $result);
            $this->table(['Node', 'Result'], collect($result['nodes'])->map(fn (array $node, string $name): array => [$name, $node['status']])->values()->all());
            $preflightPassed = count(array_filter($result['nodes'], fn (array $node): bool => $node['status'] === 'preflight-passed')) === count($result['nodes']);
            if ($result['dry_run'] && $preflightPassed && $unchanged) {
                $this->info('Shared-store preflight passed. No server settings were changed.');

                return self::SUCCESS;
            }
            if (! $result['verified']) {
                $this->error('Cluster-wide mail configuration was not verified. Review node results and reconcile before serving mail.');

                return self::FAILURE;
            }
            $this->info('Mail configuration and node health verified. SMTP/IMAP delivery, mailbox access and failover still need data-plane validation.');

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable) {
            $this->error('Mail cluster configuration failed. Check API permissions, shared storage and node connectivity. Datastore configuration and mailboxes were not migrated.');

            return self::FAILURE;
        } finally {
            $lock?->release();
        }
    }
}
