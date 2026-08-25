<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\VirtualHost;

final class CreateVirtualHost
{
    /** @param array<string, mixed> $attributes */
    public function execute(Domain $domain, array $attributes): VirtualHost
    {
        $documentRoot = trim((string) ($attributes['document_root'] ?? ''));
        $server = trim((string) ($attributes['server'] ?? 'nginx'));
        if ($documentRoot === '' || ! in_array($server, ['nginx', 'apache'], true)) {
            throw ValidationException::withMessages(['document_root' => 'A document root and supported web server are required.']);
        }

        return VirtualHost::query()->updateOrCreate(
            ['domain_id' => $domain->getKey(), 'node_id' => $attributes['node_id']],
            ['id' => (string) Str::uuid(), 'server' => $server, 'runtime' => $attributes['runtime'] ?? 'php', 'document_root' => $documentRoot, 'desired_state' => $attributes['desired_state'] ?? [], 'active' => true],
        );
    }
}
