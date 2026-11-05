<?php

namespace App\Hosting\Kubernetes;

use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use Illuminate\Contracts\Config\Repository;
use Laravel\Reverb\Servers\Reverb\Publishing\RedisClientFactory;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use React\Socket\Connector;
use RuntimeException;
use Throwable;

final class VerifiedReverbRedisClientFactory extends RedisClientFactory
{
    public function __construct(private readonly Repository $config) {}

    public function make(LoopInterface $loop, #[\SensitiveParameter] string $redisUrl): PromiseInterface
    {
        if ($this->config->get('reverb.servers.reverb.scaling.server.scheme') !== 'tls') {
            return parent::make($loop, $redisUrl);
        }
        $parts = parse_url($redisUrl);
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'rediss' || ! isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('The Reverb scaling connection requires a verified TLS Redis endpoint.');
        }
        $ca = $this->config->get('database.redis.default.context.ssl.cafile');
        if (! is_string($ca) || $ca === '' || ! is_readable($ca)) {
            throw new RuntimeException('The Reverb scaling connection requires a readable trusted CA bundle.');
        }
        parse_str($parts['query'] ?? '', $query);
        $username = $query['username'] ?? 'default';
        $password = $query['password'] ?? null;
        if (! is_string($username) || ! is_string($password) || $password === '') {
            throw new RuntimeException('The Reverb scaling connection requires Redis ACL credentials.');
        }
        // Authenticate explicitly: the installed React Redis factory only sends password-only AUTH.
        $endpoint = 'rediss://'.$parts['host'].':'.($parts['port'] ?? 6379);
        if (isset($query['db'])) {
            if (! is_string($query['db']) || ! ctype_digit($query['db'])) {
                throw new RuntimeException('The Reverb scaling database index is invalid.');
            }
            // SELECT must follow AUTH; defer it below instead of embedding it in the factory URL.
        }
        $connector = new Connector(['timeout' => 10, 'tls' => ['cafile' => $ca, 'verify_peer' => true, 'verify_peer_name' => true]], $loop);

        return (new Factory($loop, $connector))->createClient($endpoint)->then(
            static function (Client $client) use ($username, $password, $query): PromiseInterface {
                return $client->__call('AUTH', [$username, $password])->then(static function () use ($client, $query): mixed {
                    return isset($query['db']) ? $client->__call('SELECT', [$query['db']])->then(static fn (): Client => $client) : $client;
                })->then(null, static function (Throwable $exception) use ($client): never {
                    $client->close();
                    throw new RuntimeException('Reverb Redis authentication or database selection failed.');
                });
            },
            static fn (Throwable $exception): never => throw new RuntimeException('Reverb Redis TLS connection failed. Verify endpoint, CA trust and certificate identity.'),
        );
    }
}
