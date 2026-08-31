<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Queries;

use Liberu\ControlPanel\OsAdapters\Models\OsAdapter;

final class InstallationCommandReport
{
    public function __construct(private readonly ServiceStatusReport $serviceStatusReport) {}

    /** @return array{operating_system: string|null, version: string|null, commands: array<int, string>} */
    public function execute(string|int $teamId): array
    {
        $adapter = OsAdapter::query()->where('team_id', $teamId)->latest('updated_at')->first();
        $services = $this->serviceStatusReport->missing($teamId)->pluck('name')->all();

        return [
            'operating_system' => $adapter?->operating_system,
            'version' => $adapter?->version,
            'commands' => $this->commands($adapter?->operating_system, $services),
        ];
    }

    /** @param array<int, string> $services */
    private function commands(?string $operatingSystem, array $services): array
    {
        $family = match (strtolower((string) $operatingSystem)) {
            'ubuntu', 'debian' => 'debian',
            'rhel', 'centos', 'almalinux', 'rocky' => 'rhel',
            default => null,
        };

        if ($family === null) {
            return [];
        }

        $packages = collect($services)
            ->map(fn (string $service): ?string => $this->packageMap($family)[$service] ?? null)
            ->filter()
            ->flatMap(static fn (string $package): array => preg_split('/\s+/', $package) ?: [])
            ->unique()
            ->values()
            ->all();

        if ($packages === []) {
            return [];
        }

        return $family === 'debian'
            ? ['sudo apt-get update', 'sudo apt-get install -y '.implode(' ', $packages)]
            : ['sudo dnf install -y '.implode(' ', $packages)];
    }

    /** @return array<string, string> */
    private function packageMap(string $family): array
    {
        return $family === 'debian'
            ? [
                'nginx' => 'nginx', 'php-fpm' => 'php8.3-fpm', 'mysql' => 'mariadb-server',
                'postgresql' => 'postgresql', 'postfix' => 'postfix', 'dovecot' => 'dovecot-core dovecot-imapd dovecot-pop3d',
                'bind9' => 'bind9', 'certbot' => 'certbot python3-certbot-nginx',
            ]
            : [
                'nginx' => 'nginx', 'php-fpm' => 'php-fpm', 'mysql' => 'mariadb-server',
                'postgresql' => 'postgresql-server', 'postfix' => 'postfix', 'dovecot' => 'dovecot',
                'bind9' => 'bind', 'certbot' => 'certbot python3-certbot-nginx',
            ];
    }
}
