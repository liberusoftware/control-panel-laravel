<?php

namespace App\Hosting\Migrations;

use App\Hosting\ProtectedFile;
use Closure;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

final readonly class NativeBackupTransport
{
    /** @param (Closure(list<string>): Process)|null $processFactory */
    public function __construct(private ?Closure $processFactory = null) {}

    /**
     * @param  array<string, mixed>  $connection
     * @return array{format: string, provider: string, archives: list<array{domain: string, bytes: int, sha256: string}>, imported: false}
     */
    public function manifest(#[\SensitiveParameter] array $connection): array
    {
        $contents = '';
        $this->request($connection, ['operation' => 'manifest'], static function (string $chunk) use (&$contents): void {
            if (strlen($contents) + strlen($chunk) > 1024 * 1024) {
                throw new RuntimeException('The source manifest exceeds its byte limit.');
            }
            $contents .= $chunk;
        });
        try {
            $manifest = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new RuntimeException('The source returned an invalid native backup manifest.');
        }
        if (! is_array($manifest) || ($manifest['format'] ?? '') !== 'native-backup-manifest-v1'
            || ! in_array($manifest['provider'] ?? null, ['virtualmin8', 'cpanel', 'plesk', 'directadmin'], true)
            || ($manifest['imported'] ?? null) !== false || ! is_array($manifest['archives'] ?? null)
            || ! array_is_list($manifest['archives']) || count($manifest['archives']) < 1 || count($manifest['archives']) > 1000) {
            throw new RuntimeException('The native backup manifest has an unsupported format or scope.');
        }
        $seen = [];
        $archives = [];
        foreach ($manifest['archives'] as $archive) {
            $this->archive($archive, $connection['max_archive_bytes'] ?? 10737418240);
            if (isset($seen[$archive['domain']])) {
                throw new RuntimeException('The source manifest contains duplicate archive identities.');
            }
            $seen[$archive['domain']] = true;
            $archives[] = ['domain' => $archive['domain'], 'bytes' => $archive['bytes'], 'sha256' => $archive['sha256']];
        }

        return ['format' => 'native-backup-manifest-v1', 'provider' => $manifest['provider'], 'archives' => $archives, 'imported' => false];
    }

    /**
     * Download only to a new file in an operator-private staging directory; never extract or import.
     *
     * @param  array<string, mixed>  $connection
     * @param  array<string, mixed>  $archive
     * @return array{bytes: int, sha256: string}
     */
    public function download(#[\SensitiveParameter] array $connection, array $archive, string $destination): array
    {
        $this->archive($archive, $connection['max_archive_bytes'] ?? 10737418240);
        $parent = dirname($destination);
        $info = @lstat($parent);
        $uid = function_exists('posix_geteuid') ? posix_geteuid() : getmyuid();
        if (! str_starts_with($destination, '/') || $info === false || ($info['mode'] & 0170000) !== 0040000
            || $info['uid'] !== $uid || ($info['mode'] & 0077) !== 0 || realpath($parent) !== $parent
            || file_exists($destination) || is_link($destination)) {
            throw new InvalidArgumentException('Download into a new file in a canonical operator-owned private staging directory.');
        }
        $free = disk_free_space($parent);
        if ($free === false || $free < $archive['bytes'] + 128 * 1024 * 1024) {
            throw new RuntimeException('Insufficient staging space; no transfer was started.');
        }
        $temporary = tempnam($parent, '.receiving-');
        if ($temporary === false || ! chmod($temporary, 0600)) {
            throw new RuntimeException('Unable to create a protected transfer file.');
        }
        $output = @fopen($temporary, 'wb');
        if ($output === false) {
            @unlink($temporary);
            throw new RuntimeException('Unable to open a protected transfer file.');
        }
        $digest = hash_init('sha256');
        $total = 0;
        try {
            $this->request($connection, ['operation' => 'stream', ...$archive], static function (string $chunk) use ($output, $digest, &$total, $archive): void {
                $total += strlen($chunk);
                if ($total > $archive['bytes']) {
                    throw new RuntimeException('The source sent more bytes than its approved manifest.');
                }
                hash_update($digest, $chunk);
                $offset = 0;
                while ($offset < strlen($chunk)) {
                    $written = fwrite($output, substr($chunk, $offset));
                    if ($written === false || $written === 0) {
                        throw new RuntimeException('Unable to write protected staging data.');
                    }
                    $offset += $written;
                }
            });
            $sha256 = hash_final($digest);
            if ($total !== $archive['bytes'] || ! hash_equals($archive['sha256'], $sha256)) {
                throw new RuntimeException('The received archive does not match its approved length and checksum.');
            }
            if (! fflush($output) || ! fsync($output)) {
                throw new RuntimeException('The received archive could not be synchronized.');
            }
            // Publish exclusively: a concurrently created destination is never overwritten.
            if (! @link($temporary, $destination)) {
                throw new RuntimeException('The destination already exists or cannot be published safely.');
            }

            return ['bytes' => $total, 'sha256' => $sha256];
        } finally {
            fclose($output);
            @unlink($temporary);
        }
    }

    private function archive(mixed $archive, mixed $maximum): void
    {
        if (! is_int($maximum) || $maximum < 1 || $maximum > 1099511627776 || ! is_array($archive)
            || count($archive) !== 3 || ! is_string($archive['domain'] ?? null) || strlen($archive['domain']) > 253
            || ! str_contains($archive['domain'], '.') || ! preg_match('/^[a-z0-9.-]+$/D', $archive['domain'])
            || ! is_int($archive['bytes'] ?? null) || $archive['bytes'] < 1 || $archive['bytes'] > $maximum
            || ! is_string($archive['sha256'] ?? null) || ! preg_match('/^[a-f0-9]{64}$/D', $archive['sha256'])) {
            throw new InvalidArgumentException('Approve a bounded native archive domain, byte length and SHA256.');
        }
        foreach (explode('.', $archive['domain']) as $label) {
            if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $label)) {
                throw new InvalidArgumentException('The native archive domain contains an invalid hostname label.');
            }
        }
    }

    /** @param array<string, mixed> $connection
     * @param  array<string, mixed>  $request
     */
    private function request(#[\SensitiveParameter] array $connection, array $request, Closure $consume): void
    {
        $host = $connection['host'] ?? '';
        $username = $connection['username'] ?? '';
        $port = $connection['port'] ?? 22;
        $timeout = $connection['timeout'] ?? 1800;
        if (! is_string($host) || (! filter_var($host, FILTER_VALIDATE_IP) && ! preg_match('/^[a-z0-9](?:[a-z0-9.-]{0,251}[a-z0-9])?$/Di', $host))
            || ! is_string($username) || ! preg_match('/^[a-z_][a-z0-9_-]{0,63}$/Di', $username)
            || ! is_int($port) || $port < 1 || $port > 65535 || ! is_int($timeout) || $timeout < 1 || $timeout > 21600) {
            throw new InvalidArgumentException('Configure an approved SSH host, username, port and bounded timeout.');
        }
        foreach (['identity_file', 'known_hosts_file'] as $field) {
            if (! is_string($connection[$field] ?? null)) {
                throw new InvalidArgumentException('Provide protected SSH identity and pinned known-host files.');
            }
            ProtectedFile::read($connection[$field], 65536);
        }
        $arguments = ['/usr/bin/ssh', '-F', '/dev/null', '-T', '-l', $username, '-p', (string) $port,
            '-i', $connection['identity_file'], '-o', 'UserKnownHostsFile='.$connection['known_hosts_file']];
        foreach (['BatchMode=yes', 'IdentitiesOnly=yes', 'IdentityAgent=none', 'ForwardAgent=no', 'ClearAllForwardings=yes',
            'StrictHostKeyChecking=yes', 'GlobalKnownHostsFile=/dev/null', 'KnownHostsCommand=none', 'UpdateHostKeys=no',
            'PermitLocalCommand=no', 'ProxyCommand=none', 'ProxyJump=none', 'ControlMaster=no', 'ControlPath=none',
            'SendEnv=-*', 'ConnectTimeout=10', 'ServerAliveInterval=15', 'ServerAliveCountMax=2'] as $option) {
            $arguments = [...$arguments, '-o', $option];
        }
        $arguments = [...$arguments, '--', $host, '/usr/local/libexec/control-panel-migration-source'];
        $process = $this->processFactory !== null ? ($this->processFactory)($arguments) : new Process($arguments);
        $process->setTimeout($timeout)->setInput(json_encode($request, JSON_THROW_ON_ERROR)."\n")->disableOutput();
        $errorBytes = 0;
        // Symfony can drain pipes again while stopping after a consumer callback failed.
        $channel = new class()
        {
            public bool $acceptingOutput = true;
        };
        try {
            $exit = $process->run(static function (string $type, string $chunk) use ($consume, &$errorBytes, $channel): void {
                if (! $channel->acceptingOutput) {
                    return;
                }
                if ($type === Process::OUT) {
                    $consume($chunk);
                } else {
                    $errorBytes += strlen($chunk);
                    if ($errorBytes > 65536) {
                        throw new RuntimeException('The source SSH channel exceeded its error-output limit.');
                    }
                }
            });
            if ($exit !== 0) {
                throw new RuntimeException('The restricted SSH source helper did not complete successfully.');
            }
        } catch (Throwable) {
            $channel->acceptingOutput = false;
            throw new RuntimeException('Source transport failed identity, timeout, byte-limit or integrity validation. Partial data was discarded; SSH output and credentials were not printed.');
        } finally {
            $channel->acceptingOutput = false;
            if ($process->isRunning()) {
                $process->stop(0.1);
            }
        }
    }
}
