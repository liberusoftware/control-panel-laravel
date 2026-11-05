<?php

namespace App\Console\Commands;

use App\Hosting\Migrations\VirtualminSource;
use App\Hosting\ProtectedFile;
use DateTimeInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Liberu\ControlPanel\ControlCore\Actions\WriteAuditEntry;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\ControlCore\Models\Node;
use Liberu\ControlPanel\ControlCore\Models\NodeCredential;
use Liberu\ControlPanel\Kubernetes\KubernetesServiceProvider;
use Liberu\ControlPanel\Kubernetes\Models\Cluster;
use Throwable;

#[Signature('hosting:inspect-migration-source {source : Operator-owned migration source alias}')]
#[Description('Read sanitized Virtualmin 8 inventory for a team-owned Kubernetes target; never import or change the source')]
class InspectHostingMigrationSource extends Command
{
    public function handle(VirtualminSource $virtualmin, WriteAuditEntry $audit): int
    {
        if (! app()->providerIsLoaded(KubernetesServiceProvider::class) || ! app()->providerIsLoaded(ControlCoreServiceProvider::class)) {
            $this->error('Enable Kubernetes and control-core before inspecting migration sources.');

            return self::FAILURE;
        }
        $source = config('hosting-migrations.sources.'.(string) $this->argument('source'));
        if (! is_array($source) || ($source['provider'] ?? '') !== 'virtualmin8' || ! filled($source['team_id'] ?? null)
            || ! filled($source['cluster_id'] ?? null) || ! is_array($source['domains'] ?? null)
            || ! is_string($source['url'] ?? null)) {
            $this->error('Configure an operator-owned Virtualmin 8 source, approved domains, protected credentials and team-owned target cluster.');

            return self::FAILURE;
        }
        $cluster = Cluster::query()->whereKey($source['cluster_id'])->where('team_id', $source['team_id'])->whereIn('status', ['pending', 'active'])->first();
        if ($cluster === null) {
            $this->error('The destination cluster is not available in the approved team.');

            return self::FAILURE;
        }
        try {
            $credentials = $this->credentials($source);
            $result = $virtualmin->inspect(['url' => $source['url'], 'ca_certificate' => $source['ca_certificate'] ?? '', ...$credentials], $source['domains']);
            if ($this->credentials($source) !== $credentials) {
                throw new InvalidArgumentException('Source credentials changed during inspection. Review the binding and rerun.');
            }
            $fresh = $cluster->fresh();
            if ($fresh === null || $fresh->getAttribute('team_id') !== $cluster->getAttribute('team_id')
                || $fresh->getAttribute('endpoint') !== $cluster->getAttribute('endpoint') || $fresh->getAttribute('status') !== $cluster->getAttribute('status')) {
                $this->error('Target ownership or endpoint changed during inspection. Review the source binding and rerun.');

                return self::FAILURE;
            }
            $audit->execute('hosting.migration-source.inspected', (string) $cluster->getAttribute('team_id'), null, 'kubernetes-cluster', (string) $cluster->getKey(), [
                'provider' => $result['provider'], 'version' => $result['version'], 'edition' => $result['edition'],
                'approved_domains' => count($result['domains']), 'excluded_domains' => $result['excluded_domains'], 'imported' => false,
            ]);
            $this->table(['Domain', 'Source type', 'Features'], array_map(fn (array $domain): array => [$domain['domain'], $domain['type'], implode(', ', $domain['features'])], $result['domains']));
            $this->info('Source inventory inspected. No backup, import, DNS change or cutover was performed; this is not a completed migration.');

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable) {
            $this->error('Source inspection failed. Verify protected credentials, TLS, API access and source metadata. No data was imported and no upstream error details were printed.');

            return self::FAILURE;
        }
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array{username: string, password: string}
     */
    private function credentials(array $source): array
    {
        $fileCredentials = is_string($source['credentials_file'] ?? null) && $source['credentials_file'] !== '';
        $nodeCredentials = is_string($source['node_id'] ?? null) && is_string($source['credential_id'] ?? null);
        if ($fileCredentials === $nodeCredentials) {
            throw new InvalidArgumentException('Choose exactly one protected credential file or team-owned node/credential binding.');
        }
        if ($nodeCredentials) {
            $node = Node::query()->whereKey($source['node_id'])->where('team_id', $source['team_id'])->whereIn('status', ['pending', 'active'])->first();
            $credential = NodeCredential::query()->whereKey($source['credential_id'])->where('node_id', $source['node_id'])
                ->where('team_id', $source['team_id'])->where('type', 'api')->where('status', 'active')->first();
            $originHost = parse_url($source['url'], PHP_URL_HOST);
            $expiry = $credential?->getAttribute('expires_at');
            $metadata = $credential?->getAttribute('metadata');
            if ($node === null || $credential === null || ! is_string($originHost)
                || strtolower(rtrim((string) $node->getAttribute('hostname'), '.')) !== strtolower(rtrim($originHost, '.'))
                || ! is_array($metadata) || ($metadata['provider'] ?? '') !== 'virtualmin8'
                || rtrim((string) ($metadata['origin'] ?? ''), '/') !== rtrim($source['url'], '/')
                || ($expiry !== null && (! $expiry instanceof DateTimeInterface || $expiry->getTimestamp() <= time()))) {
                throw new InvalidArgumentException('Use an active, unexpired API credential on the approved team-owned source node and exact Virtualmin origin.');
            }
            $username = $credential->getAttribute('username');
            $password = $credential->getAttribute('secret');
            if (! is_string($username) || ! is_string($password)) {
                throw new InvalidArgumentException('The source API credential requires a username and encrypted password.');
            }

            return ['username' => $username, 'password' => $password];
        }
        $contents = ProtectedFile::read($source['credentials_file'], 16384);
        try {
            $credentials = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new InvalidArgumentException('The source credential file has invalid JSON.');
        }
        if (! is_array($credentials) || count($credentials) !== 2 || ! is_string($credentials['username'] ?? null) || ! is_string($credentials['password'] ?? null)) {
            throw new InvalidArgumentException('Source credential JSON must contain only username and password strings.');
        }

        return ['username' => $credentials['username'], 'password' => $credentials['password']];
    }
}
