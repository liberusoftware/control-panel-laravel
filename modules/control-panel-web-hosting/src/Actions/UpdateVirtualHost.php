<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\VirtualHost;

final class UpdateVirtualHost
{
    /** @param array<string, mixed> $attributes */
    public function execute(VirtualHost $virtualHost, array $attributes): VirtualHost
    {
        $domainId = $attributes['domain_id'] ?? $virtualHost->domain_id;
        $server = trim((string) ($attributes['server'] ?? $virtualHost->server));
        $documentRoot = trim((string) ($attributes['document_root'] ?? $virtualHost->document_root));
        $domain = Domain::query()->whereKey($domainId)->where('team_id', $virtualHost->domain->team_id)->first();

        if ($domain === null) {
            throw ValidationException::withMessages(['domain_id' => 'The domain must belong to the current team.']);
        }

        if (! in_array($server, ['nginx', 'apache'], true)) {
            throw ValidationException::withMessages(['server' => 'The web server must be nginx or apache.']);
        }

        if (! str_starts_with($documentRoot, '/') || mb_strlen($documentRoot) > 2048) {
            throw ValidationException::withMessages(['document_root' => 'The document root must be an absolute path.']);
        }

        $conflict = VirtualHost::query()->where('domain_id', $domainId)->where('server', $server)->where($virtualHost->getKeyName(), '!=', $virtualHost->getKey())->exists();
        if ($conflict) {
            throw ValidationException::withMessages(['server' => 'A virtual host already exists for this domain and server.']);
        }

        $virtualHost->forceFill([
            'domain_id' => $domainId,
            'node_id' => $attributes['node_id'] ?? $virtualHost->node_id,
            'server' => $server,
            'runtime' => $attributes['runtime'] ?? $virtualHost->runtime,
            'document_root' => $documentRoot,
            'desired_state' => $attributes['desired_state'] ?? $virtualHost->desired_state,
            'active' => $attributes['active'] ?? $virtualHost->active,
        ])->save();

        return $virtualHost->refresh();
    }
}
