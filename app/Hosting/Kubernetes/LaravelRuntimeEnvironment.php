<?php

namespace App\Hosting\Kubernetes;

use Dotenv\Dotenv;
use InvalidArgumentException;
use Throwable;

final class LaravelRuntimeEnvironment
{
    /** @param array<string, mixed> $settings
     * @return array{values: array<string, string>, contents: string}
     */
    public function build(array $settings): array
    {
        $path = $settings['environment_file'] ?? null;
        $this->ensure(is_string($path) && str_starts_with($path, '/') && is_file($path) && is_readable($path), 'Choose an existing protected production environment file.');
        for ($parent = $path; $parent !== '/'; $parent = dirname($parent)) {
            $this->ensure(! is_link($parent), 'Production environment files must not traverse symbolic links.');
        }
        $info = stat($path);
        $this->ensure($info !== false && ($info['mode'] & 0077) === 0 && $info['size'] <= 65536
            && (! function_exists('posix_geteuid') || $info['uid'] === posix_geteuid()), 'Production environment files must be operator-owned, private and at most 64 KiB.');
        try {
            $contents = file_get_contents($path);
            $values = Dotenv::parse($contents);
        } catch (Throwable) {
            throw new InvalidArgumentException('The production environment file has invalid dotenv syntax.');
        }
        $key = $settings['app_key'] ?? null;
        $this->ensure(is_string($key) && str_starts_with($key, 'base64:') && strlen(base64_decode(substr($key, 7), true) ?: '') === 32, 'Supply a stable AES-256 Laravel application key.');
        $url = $settings['app_url'] ?? null;
        $parts = is_string($url) ? parse_url($url) : false;
        $this->ensure(is_array($parts) && ($parts['scheme'] ?? '') === 'https' && ! empty($parts['host'])
            && ! isset($parts['user']) && ! isset($parts['pass']) && ! isset($parts['query']) && ! isset($parts['fragment'])
            && ! preg_match('/[\x00-\x20\x7f]/', $url), 'Configure an HTTPS site URL.');
        $database = $settings['database'] ?? [];
        $this->ensure(is_array($database) && in_array($database['connection'] ?? null, ['pgsql', 'mysql'], true), 'Select an approved PostgreSQL or MySQL database binding.');
        foreach (['host', 'database', 'username', 'password'] as $field) {
            $this->ensure(is_string($database[$field] ?? null) && $database[$field] !== '' && ! preg_match('/[\x00-\x1f\x7f]/', $database[$field]), 'Complete the operator-approved database binding.');
        }
        $port = $database['port'] ?? ($database['connection'] === 'pgsql' ? 5432 : 3306);
        $this->ensure(is_int($port) && $port > 0 && $port <= 65535, 'Use a valid database port.');
        foreach ($values as $name => $value) {
            $this->ensure((bool) preg_match('/^[A-Z][A-Z0-9_]*$/D', $name)
                && is_string($value) && ! str_contains($value, "\0"), 'Use uppercase dotenv keys and string values.');
            $this->ensure(! preg_match('/^(?:GITHUB_|GITLAB_|CI_|KUBE|HOSTING_KUBERNETES_|DB_ROOT_|MYSQL_ROOT_)/', $name), 'Do not import CI, cluster-administrator or database-root credentials into tenant applications.');
        }

        // Environment variables override the mounted dotenv file in Laravel's immutable repository.
        return ['contents' => $contents, 'values' => [...$values, 'APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_KEY' => $key, 'APP_URL' => $url,
            'DB_URL' => '', 'DATABASE_URL' => '', 'DB_CONNECTION' => $database['connection'], 'DB_HOST' => $database['host'],
            'DB_PORT' => (string) $port, 'DB_DATABASE' => $database['database'], 'DB_USERNAME' => $database['username'],
            'DB_PASSWORD' => $database['password'], 'DB_SSLMODE' => $database['connection'] === 'pgsql' ? 'verify-full' : '',
        ]];
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new InvalidArgumentException($message);
        }
    }
}
