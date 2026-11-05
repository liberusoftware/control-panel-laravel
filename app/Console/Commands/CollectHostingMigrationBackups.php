<?php

namespace App\Console\Commands;

use App\Hosting\Migrations\NativeBackupTransport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Liberu\ControlPanel\ControlCore\Actions\WriteAuditEntry;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\Kubernetes\KubernetesServiceProvider;
use Liberu\ControlPanel\Kubernetes\Models\Cluster;
use RuntimeException;
use Throwable;

#[Signature('hosting:collect-migration-backups {source : Operator-owned migration source alias}')]
#[Description('Collect approved native backups over pinned SSH into private staging; never extract, import or cut over services')]
class CollectHostingMigrationBackups extends Command
{
    public function handle(NativeBackupTransport $transport, WriteAuditEntry $audit, Filesystem $files): int
    {
        if (! app()->providerIsLoaded(KubernetesServiceProvider::class) || ! app()->providerIsLoaded(ControlCoreServiceProvider::class)) {
            $this->error('Enable Kubernetes and control-core before collecting migration backups.');

            return self::FAILURE;
        }
        $source = config('hosting-migrations.sources.'.(string) $this->argument('source'));
        if (! is_array($source) || ! in_array($source['provider'] ?? '', ['virtualmin8', 'cpanel', 'plesk', 'directadmin'], true)
            || ! is_string($source['team_id'] ?? null) || $source['team_id'] === '' || ! is_string($source['cluster_id'] ?? null)
            || ! is_array($source['transport'] ?? null) || ! is_array($source['domains'] ?? null)
            || ! array_is_list($source['domains']) || count($source['domains']) < 1 || count($source['domains']) > 1000
            || count(array_filter($source['domains'], 'is_string')) !== count($source['domains'])
            || count(array_unique($source['domains'])) !== count($source['domains'])
            || (in_array('*', $source['domains'], true) && $source['domains'] !== ['*'])) {
            $this->error('Configure an operator-owned provider, team, cluster, protected SSH transport and explicit domain scope.');

            return self::FAILURE;
        }
        $cluster = Cluster::query()->whereKey($source['cluster_id'])->where('team_id', $source['team_id'])->whereIn('status', ['pending', 'active'])->first();
        if ($cluster === null) {
            $this->error('The destination cluster is not available in the approved team.');

            return self::FAILURE;
        }
        $workspace = null;
        $complete = false;
        try {
            $root = config('hosting-migrations.staging_root');
            $maximum = config('hosting-migrations.max_collection_bytes', 1073741824);
            $uid = function_exists('posix_geteuid') ? posix_geteuid() : getmyuid();
            $metadata = is_string($root) ? @lstat($root) : false;
            if (! is_string($root) || ! str_starts_with($root, '/') || realpath($root) !== $root || $metadata === false
                || ($metadata['mode'] & 0170000) !== 0040000 || ($metadata['mode'] & 0077) !== 0 || $metadata['uid'] !== $uid
                || ! is_int($maximum) || $maximum < 1 || $maximum > 1099511627776) {
                throw new RuntimeException('Configure a canonical operator-owned 0700 staging root and bounded total bytes.');
            }
            $manifest = $transport->manifest($source['transport']);
            if ($manifest['provider'] !== $source['provider']) {
                throw new RuntimeException('Source provider does not match the approved binding.');
            }
            $archives = array_values(array_filter($manifest['archives'], fn (array $archive): bool => $source['domains'] === ['*'] || in_array($archive['domain'], $source['domains'], true)));
            if (count($archives) < 1 || ($source['domains'] !== ['*'] && count($archives) !== count($source['domains']))) {
                throw new RuntimeException('The source manifest is missing approved domain backups.');
            }
            $total = 0;
            foreach ($archives as $archive) {
                if ($archive['bytes'] > $maximum - $total) {
                    throw new RuntimeException('Approved backups exceed the total staging byte limit.');
                }
                $total += $archive['bytes'];
            }
            // Allow the manifest and a safety margin before creating any staging data.
            $free = disk_free_space($root);
            if ($free === false || $free < $total + 128 * 1024 * 1024) {
                throw new RuntimeException('Insufficient space for the complete approved collection.');
            }
            $id = (string) Str::uuid();
            $candidate = $root.'/migration-'.$id;
            if (! mkdir($candidate, 0700)) {
                throw new RuntimeException('Unable to create private staging.');
            }
            $workspace = $candidate;
            foreach ($archives as $archive) {
                // Domain hashes avoid filesystem NAME_MAX limits and untrusted path components.
                $transport->download($source['transport'], $archive, $workspace.'/'.hash('sha256', $archive['domain']).'.native');
            }
            $fresh = $cluster->fresh();
            if ($fresh === null || Arr::only($fresh->getAttributes(), ['team_id', 'endpoint', 'status']) !== Arr::only($cluster->getAttributes(), ['team_id', 'endpoint', 'status'])) {
                throw new RuntimeException('The approved destination changed during collection.');
            }
            $receipt = ['format' => 'native-backup-collection-v1', 'id' => $id, 'team_id' => $source['team_id'], 'cluster_id' => $source['cluster_id'],
                'provider' => $source['provider'], 'archives' => $archives, 'imported' => false];
            $output = fopen($workspace.'/manifest.json', 'xb');
            if ($output === false) {
                throw new RuntimeException('Unable to create a collection receipt.');
            }
            try {
                $json = json_encode($receipt, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)."\n";
                if (! chmod($workspace.'/manifest.json', 0600) || fwrite($output, $json) !== strlen($json) || ! fflush($output) || ! fsync($output)) {
                    throw new RuntimeException('Unable to persist a protected collection receipt.');
                }
            } finally {
                fclose($output);
            }
            $audit->execute('hosting.migration-backups.collected', $source['team_id'], null, 'kubernetes-cluster', $source['cluster_id'], [
                'collection_id' => $id, 'provider' => $source['provider'], 'archives' => count($archives), 'bytes' => $total, 'imported' => false,
            ]);
            $complete = true;
            $this->info('Verified native backups collected in private staging: '.$workspace);
            $this->warn('Sensitive unencrypted staging: encrypt retained backups and clean up after review. No extraction, import, DNS change or cutover was performed.');

            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('Backup collection failed scope, ownership, capacity, SSH or integrity checks. Incomplete staging was discarded; source details and credentials were not printed.');

            return self::FAILURE;
        } finally {
            if (! $complete && $workspace !== null && is_dir($workspace)) {
                $files->deleteDirectory($workspace);
            }
        }
    }
}
