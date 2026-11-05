<?php

use App\Hosting\Migrations\NativeBackupTransport;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    $this->workspace = sys_get_temp_dir().'/native-transfer-'.bin2hex(random_bytes(8));
    mkdir($this->workspace, 0700);
    $this->workspace = realpath($this->workspace);
    $this->identity = $this->workspace.'/identity';
    $this->knownHosts = $this->workspace.'/known-hosts';
    file_put_contents($this->identity, 'PRIVATE-IDENTITY-FIXTURE');
    file_put_contents($this->knownHosts, 'source.example.test ssh-ed25519 public-fixture');
    chmod($this->identity, 0600);
    chmod($this->knownHosts, 0600);
    $this->connection = ['host' => 'source.example.test', 'username' => 'migration', 'identity_file' => $this->identity, 'known_hosts_file' => $this->knownHosts];
    $this->data = str_repeat('native-backup-data-', 1000);
    $this->archive = ['domain' => 'example.test', 'bytes' => strlen($this->data), 'sha256' => hash('sha256', $this->data)];
    $this->payload = $this->data;
    $this->exitCode = 0;
    $this->errorOutput = '';
    $this->arguments = [];
    $this->transport = new NativeBackupTransport(function (array $arguments): Process {
        $this->arguments = $arguments;

        // Exercise the real process streaming/exit-status path without invoking SSH or connecting anywhere.
        return new Process([PHP_BINARY, '-r', 'file_put_contents($argv[4], stream_get_contents(STDIN)); fwrite(STDOUT, base64_decode($argv[1])); fwrite(STDERR, $argv[2]); exit((int) $argv[3]);',
            base64_encode($this->payload), $this->errorOutput, (string) $this->exitCode, $this->workspace.'/request.json']);
    });
});

afterEach(function (): void {
    (new Filesystem())->deleteDirectory($this->workspace);
});

it('streams a checksum-pinned native archive privately and publishes only after successful verification', function (): void {
    $destination = $this->workspace.'/received.native';
    $result = $this->transport->download($this->connection, $this->archive, $destination);
    expect($result)->toMatchArray(['bytes' => strlen($this->data), 'sha256' => hash('sha256', $this->data)])
        ->and(file_get_contents($destination))->toBe($this->data)->and(fileperms($destination) & 0777)->toBe(0600)
        ->and(glob($this->workspace.'/.receiving-*'))->toBe([]);
    $request = json_decode(file_get_contents($this->workspace.'/request.json'), true, flags: JSON_THROW_ON_ERROR);
    expect($request)->toBe(['operation' => 'stream', ...$this->archive]);
});

it('pins SSH identities and host keys and disables inherited configs, agents and forwarding', function (): void {
    $this->transport->download($this->connection, $this->archive, $this->workspace.'/received.native');
    expect($this->arguments)->toContain('StrictHostKeyChecking=yes', 'IdentityAgent=none', 'IdentitiesOnly=yes', 'BatchMode=yes',
        'ClearAllForwardings=yes', 'ForwardAgent=no', 'ProxyCommand=none', 'ProxyJump=none', 'GlobalKnownHostsFile=/dev/null',
        'UserKnownHostsFile='.$this->knownHosts, '/usr/local/libexec/control-panel-migration-source')
        ->and(implode(' ', $this->arguments))->not->toContain('PRIVATE-IDENTITY-FIXTURE');
});

it('discards failed, truncated, oversized or checksum-mismatched streams without publishing any destination', function (string $failure): void {
    match ($failure) {
        'nonzero-exit' => $this->exitCode = 1,
        'truncated' => $this->payload = substr($this->data, 1),
        'oversized' => $this->payload .= 'extra',
        'wrong-checksum' => $this->payload = str_repeat('x', strlen($this->data)),
    };
    $this->errorOutput = 'private-upstream-error';
    $destination = $this->workspace.'/received.native';
    try {
        $this->transport->download($this->connection, $this->archive, $destination);
        $this->fail('Invalid source stream must not be accepted.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->not->toContain('private-upstream-error');
    }
    expect(file_exists($destination))->toBeFalse()->and(glob($this->workspace.'/.receiving-*'))->toBe([]);
})->with(['nonzero-exit', 'truncated', 'oversized', 'wrong-checksum']);

it('never overwrites an existing staging artifact', function (): void {
    $destination = $this->workspace.'/existing.native';
    file_put_contents($destination, 'keep-existing-data');
    expect(fn () => $this->transport->download($this->connection, $this->archive, $destination))->toThrow(InvalidArgumentException::class);
    expect(file_get_contents($destination))->toBe('keep-existing-data')->and($this->arguments)->toBe([]);
});

it('rejects public staging directories before opening an SSH connection', function (): void {
    chmod($this->workspace, 0755);
    expect(fn () => $this->transport->download($this->connection, $this->archive, $this->workspace.'/received.native'))->toThrow(InvalidArgumentException::class);
    expect($this->arguments)->toBe([]);
});

it('rejects invalid SSH destinations or exposed identity files before starting any process', function (string $problem): void {
    match ($problem) {
        'option-injection' => $this->connection['host'] = '-oProxyCommand=anything',
        'username-injection' => $this->connection['username'] = 'root;anything',
        'plaintext-url' => $this->connection['host'] = 'http://source.example.test',
        'invalid-port' => $this->connection['port'] = 70000,
        'exposed-key' => chmod($this->identity, 0644),
    };
    expect(fn () => $this->transport->manifest($this->connection))->toThrow(InvalidArgumentException::class);
    expect($this->arguments)->toBe([]);
})->with(['option-injection', 'username-injection', 'plaintext-url', 'invalid-port', 'exposed-key']);

it('returns only a bounded sanitized manifest and never claims a provider import', function (): void {
    $this->payload = json_encode(['format' => 'native-backup-manifest-v1', 'provider' => 'virtualmin8', 'archives' => [$this->archive], 'imported' => false, 'unexpected_secret' => 'private-source-secret'], JSON_THROW_ON_ERROR);
    $manifest = $this->transport->manifest($this->connection);
    expect($manifest['archives'])->toBe([$this->archive])->and($manifest['imported'])->toBeFalse()
        ->and(json_encode($manifest))->not->toContain('private-source-secret');
});

it('refuses malformed, duplicated or oversized source manifests', function (string $failure): void {
    $manifest = ['format' => 'native-backup-manifest-v1', 'provider' => 'virtualmin8', 'archives' => [$this->archive], 'imported' => false];
    match ($failure) {
        'duplicate' => $manifest['archives'][] = $this->archive,
        'wrong-format' => $manifest['format'] = 'unexpected',
        'unbounded-archive' => $manifest['archives'][0]['bytes'] = 1099511627777,
        'false-import-claim' => $manifest['imported'] = true,
    };
    $this->payload = json_encode($manifest, JSON_THROW_ON_ERROR);
    expect(fn () => $this->transport->manifest($this->connection))->toThrow(Exception::class);
})->with(['duplicate', 'wrong-format', 'unbounded-archive', 'false-import-claim']);

it('stops an overflowing stream promptly and never publishes partial data', function (): void {
    $transport = new NativeBackupTransport(fn (array $arguments): Process => new Process([PHP_BINARY, '-r', 'while (true) { fwrite(STDOUT, str_repeat("x", 65536)); }']));
    $this->connection['timeout'] = 1;
    $destination = $this->workspace.'/oversized.native';
    $started = microtime(true);
    expect(fn () => $transport->download($this->connection, $this->archive, $destination))->toThrow(RuntimeException::class);
    expect(microtime(true) - $started)->toBeLessThan(4)
        ->and(file_exists($destination))->toBeFalse()->and(glob($this->workspace.'/.receiving-*'))->toBe([]);
});
