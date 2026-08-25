<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class GitDeployment extends Model
{
    use HasUuids;

    protected $table = 'control_panel_git_deployments';

    protected $fillable = [
        'domain_id', 'team_id', 'connected_account_id', 'use_oauth', 'container_id',
        'kubernetes_pod_name', 'kubernetes_namespace', 'repository_url', 'repository_type',
        'branch', 'deploy_path', 'deploy_key', 'webhook_secret', 'status', 'deployment_log',
        'build_command', 'deploy_command', 'auto_deploy', 'last_deployed_at', 'last_commit_hash',
    ];

    protected $hidden = ['deploy_key', 'webhook_secret'];

    protected function casts(): array
    {
        return [
            'use_oauth' => 'boolean', 'deploy_key' => 'encrypted', 'webhook_secret' => 'encrypted',
            'auto_deploy' => 'boolean', 'last_deployed_at' => 'datetime',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function isDeployed(): bool
    {
        return $this->status === 'deployed';
    }

    public function isDeploying(): bool
    {
        return in_array($this->status, ['cloning', 'updating'], true);
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPrivate(): bool
    {
        return filled($this->deploy_key) || $this->use_oauth;
    }

    public function usesOAuth(): bool
    {
        return $this->use_oauth && filled($this->connected_account_id);
    }

    public function hasContainerIsolation(): bool
    {
        return filled($this->container_id) || filled($this->kubernetes_pod_name);
    }

    public function getRepositoryNameAttribute(): string
    {
        return str_replace('.git', '', basename(rtrim($this->repository_url, '/')));
    }

    public function getFullPathAttribute(): string
    {
        return rtrim($this->deploy_path, '/');
    }

    public static function detectRepositoryType(string $url): string
    {
        return match (true) {
            str_contains($url, 'github.com') => 'github',
            str_contains($url, 'gitlab.com') => 'gitlab',
            str_contains($url, 'bitbucket.org') => 'bitbucket',
            default => 'other',
        };
    }

    public static function validateGitHubWebhook(string $payload, string $signature, string $secret): bool
    {
        return hash_equals('sha256='.hash_hmac('sha256', $payload, $secret), $signature);
    }

    public static function validateGitLabWebhook(string $token, string $secret): bool
    {
        return hash_equals($secret, $token);
    }
}
