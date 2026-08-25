<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\GitDeployment;

final class RegisterGitDeployment
{
    /** @param array<string, mixed> $attributes */
    public function execute(Domain $domain, array $attributes): GitDeployment
    {
        $url = trim((string) ($attributes['repository_url'] ?? ''));
        $path = trim((string) ($attributes['deploy_path'] ?? ''));
        if (! $this->isValidRepositoryUrl($url)) {
            throw ValidationException::withMessages(['repository_url' => 'A valid HTTPS or SSH repository URL is required.']);
        }
        if ($path === '' || ! str_starts_with($path, '/')) {
            throw ValidationException::withMessages(['deploy_path' => 'The deployment path must be an absolute path.']);
        }
        if (($attributes['use_oauth'] ?? false) && blank($attributes['connected_account_id'] ?? null)) {
            throw ValidationException::withMessages(['connected_account_id' => 'An OAuth account is required when OAuth is enabled.']);
        }

        return GitDeployment::query()->create([
            'id' => (string) Str::uuid(), 'domain_id' => $domain->getKey(), 'team_id' => $domain->team_id,
            'connected_account_id' => $attributes['connected_account_id'] ?? null,
            'use_oauth' => (bool) ($attributes['use_oauth'] ?? false), 'container_id' => $attributes['container_id'] ?? null,
            'kubernetes_pod_name' => $attributes['kubernetes_pod_name'] ?? null, 'kubernetes_namespace' => $attributes['kubernetes_namespace'] ?? null,
            'repository_url' => $url, 'repository_type' => GitDeployment::detectRepositoryType($url),
            'branch' => trim((string) ($attributes['branch'] ?? 'main')) ?: 'main', 'deploy_path' => $path,
            'deploy_key' => $attributes['deploy_key'] ?? null, 'webhook_secret' => $attributes['webhook_secret'] ?? Str::random(40),
            'status' => 'pending', 'deployment_log' => $attributes['deployment_log'] ?? null,
            'build_command' => $attributes['build_command'] ?? null, 'deploy_command' => $attributes['deploy_command'] ?? null,
            'auto_deploy' => (bool) ($attributes['auto_deploy'] ?? false), 'last_commit_hash' => null,
        ]);
    }

    private function isValidRepositoryUrl(string $url): bool
    {
        return (bool) preg_match('/^(https:\/\/|ssh:\/\/|git@)[^\s]+$/', $url);
    }
}
