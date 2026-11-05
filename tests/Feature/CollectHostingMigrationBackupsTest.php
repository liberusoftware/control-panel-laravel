<?php

use App\Hosting\Migrations\NativeBackupTransport;
use Illuminate\Filesystem\Filesystem;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\ControlCore\Models\AuditEntry;
use Liberu\ControlPanel\Kubernetes\Actions\RegisterCluster;
use Liberu\ControlPanel\Kubernetes\Actions\SuspendCluster;
use Liberu\ControlPanel\Kubernetes\KubernetesServiceProvider;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    app()->register(ControlCoreServiceProvider::class);
    app()->register(KubernetesServiceProvider::class);
    $this->artisan('migrate');
    $this->cluster = app(RegisterCluster::class)->execute(['team_id' => 'team', 'name' => 'Migration target', 'endpoint' => 'https://cluster.example.test']);
    $this->workspace = sys_get_temp_dir().'/migration-collection-'.bin2hex(random_bytes(8));
    mkdir($this->workspace, 0700);
    $this->workspace = realpath($this->workspace);
    mkdir($this->workspace.'/staging', 0700);
    foreach (['identity', 'known-hosts'] as $file) {
        file_put_contents($this->workspace.'/'.$file, 'private-fixture');
        chmod($this->workspace.'/'.$file, 0600);
    }
    $this->payload = 'private-native-backup';
    $this->archive = ['domain' => 'example.test', 'bytes' => strlen($this->payload), 'sha256' => hash('sha256', $this->payload)];
    $this->manifest = ['format' => 'native-backup-manifest-v1', 'provider' => 'virtualmin8', 'archives' => [$this->archive], 'imported' => false];
    $this->calls = 0;
    $this->afterTransfer = null;
    $this->brokenTransfer = false;
    $this->failAt = 0;
    app()->instance(NativeBackupTransport::class, new NativeBackupTransport(function (array $arguments): Process {
        $this->calls++;
        if ($this->calls > 1 && $this->afterTransfer !== null) {
            ($this->afterTransfer)();
        }
        $output = $this->calls === 1 ? json_encode($this->manifest, JSON_THROW_ON_ERROR) : (($this->brokenTransfer || $this->calls === $this->failAt) ? 'truncated' : $this->payload);

        return new Process([PHP_BINARY, '-r', 'stream_get_contents(STDIN); fwrite(STDOUT, base64_decode($argv[1]));', base64_encode($output)]);
    }));
    config(['hosting-migrations.staging_root' => $this->workspace.'/staging', 'hosting-migrations.sources.legacy' => [
        'provider' => 'virtualmin8', 'team_id' => 'team', 'cluster_id' => (string) $this->cluster->getKey(), 'domains' => ['example.test'],
        'transport' => ['host' => 'source.example.test', 'username' => 'root', 'identity_file' => $this->workspace.'/identity', 'known_hosts_file' => $this->workspace.'/known-hosts'],
    ]]);
});

afterEach(function (): void {
    (new Filesystem())->deleteDirectory($this->workspace);
});

it('collects verified backups with a private receipt and explicitly performs no import or cutover', function (): void {
    $this->artisan('hosting:collect-migration-backups legacy')->expectsOutputToContain('No extraction, import, DNS change or cutover')->assertSuccessful();
    $directories = glob($this->workspace.'/staging/migration-*');
    expect($directories)->toHaveCount(1);
    $directory = $directories[0];
    $receipt = json_decode(file_get_contents($directory.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
    expect($receipt)->toMatchArray(['team_id' => 'team', 'provider' => 'virtualmin8', 'imported' => false])
        ->and($receipt['archives'])->toBe([$this->archive])->and(fileperms($directory) & 0777)->toBe(0700)
        ->and(fileperms($directory.'/manifest.json') & 0777)->toBe(0600)
        ->and(file_get_contents($directory.'/'.hash('sha256', 'example.test').'.native'))->toBe($this->payload);
    $audit = AuditEntry::query()->where('event', 'hosting.migration-backups.collected')->firstOrFail();
    expect($audit->getAttribute('context'))->toMatchArray(['archives' => 1, 'bytes' => strlen($this->payload), 'imported' => false])
        ->and(json_encode($audit))->not->toContain('private-fixture', 'private-native-backup', 'source.example.test');
});

it('rejects missing ownership, inactive targets or public staging before making a source request', function (string $problem): void {
    match ($problem) {
        'foreign-team' => config(['hosting-migrations.sources.legacy.team_id' => 'other-team']),
        'suspended' => app(SuspendCluster::class)->execute($this->cluster),
        'public-directory' => chmod($this->workspace.'/staging', 0755),
        'mixed-wildcard' => config(['hosting-migrations.sources.legacy.domains' => ['*', 'example.test']]),
    };
    $this->artisan('hosting:collect-migration-backups legacy')->assertFailed();
    expect($this->calls)->toBe(0)->and(glob($this->workspace.'/staging/*'))->toBe([]);
})->with(['foreign-team', 'suspended', 'public-directory', 'mixed-wildcard']);

it('rejects provider mismatch, missing scope and aggregate limits before streaming any backup', function (string $problem): void {
    match ($problem) {
        'provider' => $this->manifest['provider'] = 'cpanel',
        'scope' => config(['hosting-migrations.sources.legacy.domains' => ['missing.test']]),
        'capacity' => config(['hosting-migrations.max_collection_bytes' => 1]),
    };
    $this->artisan('hosting:collect-migration-backups legacy')->assertFailed();
    expect($this->calls)->toBe(1)->and(glob($this->workspace.'/staging/*'))->toBe([]);
})->with(['provider', 'scope', 'capacity']);

it('cleans the complete collection and creates no success audit after transfer or lifecycle failure', function (string $problem): void {
    if ($problem === 'integrity') {
        $this->brokenTransfer = true;
    } else {
        $this->afterTransfer = fn () => app(SuspendCluster::class)->execute($this->cluster);
    }
    $this->artisan('hosting:collect-migration-backups legacy')->assertFailed();
    expect($this->calls)->toBe(2)->and(glob($this->workspace.'/staging/*'))->toBe([])
        ->and(AuditEntry::query()->where('event', 'hosting.migration-backups.collected')->exists())->toBeFalse();
})->with(['integrity', 'lifecycle']);

it('downloads only approved domains and excludes other source backups', function (): void {
    $this->manifest['archives'][] = [...$this->archive, 'domain' => 'other.test'];
    $this->artisan('hosting:collect-migration-backups legacy')->assertSuccessful();
    expect($this->calls)->toBe(2);
    $directory = glob($this->workspace.'/staging/migration-*')[0];
    expect(glob($directory.'/*.native'))->toHaveCount(1);
});

it('removes previously verified files when a later archive in the collection fails', function (): void {
    $this->manifest['archives'][] = [...$this->archive, 'domain' => 'second.test'];
    config(['hosting-migrations.sources.legacy.domains' => ['example.test', 'second.test']]);
    $this->failAt = 3;
    $this->artisan('hosting:collect-migration-backups legacy')->assertFailed();
    expect($this->calls)->toBe(3)->and(glob($this->workspace.'/staging/*'))->toBe([])
        ->and(AuditEntry::query()->where('event', 'hosting.migration-backups.collected')->exists())->toBeFalse();
});

it('accepts an explicitly approved whole-server collection without claiming an import', function (): void {
    $this->manifest['archives'][] = [...$this->archive, 'domain' => 'second.test'];
    config(['hosting-migrations.sources.legacy.domains' => ['*']]);
    $this->artisan('hosting:collect-migration-backups legacy')->assertSuccessful();
    $directory = glob($this->workspace.'/staging/migration-*')[0];
    expect($this->calls)->toBe(3)->and(glob($directory.'/*.native'))->toHaveCount(2);
});
