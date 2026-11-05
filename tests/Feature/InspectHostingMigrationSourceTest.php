<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNode;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\RevokeNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\UpdateNodeCredential;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\ControlCore\Enums\CredentialStatus;
use Liberu\ControlPanel\ControlCore\Models\AuditEntry;
use Liberu\ControlPanel\ControlCore\Models\NodeCredential;
use Liberu\ControlPanel\Kubernetes\Actions\ArchiveCluster;
use Liberu\ControlPanel\Kubernetes\Actions\RegisterCluster;
use Liberu\ControlPanel\Kubernetes\Actions\SuspendCluster;
use Liberu\ControlPanel\Kubernetes\KubernetesServiceProvider;

beforeEach(function (): void {
    app()->register(ControlCoreServiceProvider::class);
    app()->register(KubernetesServiceProvider::class);
    $this->artisan('migrate');
    $this->cluster = app(RegisterCluster::class)->execute(['team_id' => 'team', 'name' => 'Migration destination', 'endpoint' => 'https://destination.example.test']);
    $this->credentialFile = tempnam(sys_get_temp_dir(), 'hosting-source-');
    chmod($this->credentialFile, 0600);
    file_put_contents($this->credentialFile, json_encode(['username' => 'root', 'password' => 'private-api-password'], JSON_THROW_ON_ERROR));
    config(['hosting-migrations.sources.legacy' => ['provider' => 'virtualmin8', 'team_id' => 'team', 'cluster_id' => $this->cluster->getKey(),
        'url' => 'https://source.example.test:10000', 'credentials_file' => $this->credentialFile, 'domains' => ['example.test']]]);
    $this->apiFailure = false;
    $this->afterSourceRequest = null;
    Http::preventStrayRequests();
    Http::fake(function (Request $request) {
        if ($this->afterSourceRequest !== null) {
            ($this->afterSourceRequest)();
        }
        if ($this->apiFailure) {
            return Http::response(['status' => 'failure', 'error' => 'private-api-password'], 401);
        }
        if ($request['program'] === 'info') {
            return Http::response(['command' => 'info', 'status' => 'success', 'output' => "host:\n    virtualmin version: 8.2.0\n    virtualmin edition: Pro\n"]);
        }

        return Http::response(['command' => 'list-domains', 'status' => 'success', 'data' => [['name' => 'example.test', 'values' => [
            'id' => ['12345'], 'username' => ['website'], 'home_directory' => ['/home/website'], 'type' => ['Top-level server'],
            'features' => ['web dns mail mysql'], 'password' => ['private-domain-password'],
        ]]]]);
    });
});

afterEach(function (): void {
    @unlink($this->credentialFile);
});

it('records only sanitized source-inspection evidence and performs no source or target mutation', function (): void {
    $this->artisan('hosting:inspect-migration-source legacy')->expectsOutputToContain('No backup, import, DNS change or cutover')->assertSuccessful();
    $audit = AuditEntry::query()->where('event', 'hosting.migration-source.inspected')->firstOrFail();
    expect($audit->getAttribute('team_id'))->toBe('team')
        ->and($audit->getAttribute('context'))->toMatchArray(['approved_domains' => 1, 'imported' => false, 'edition' => 'pro'])
        ->and(json_encode($audit))->not->toContain('private-api-password', 'private-domain-password');
    Http::assertSentCount(2);
    Http::assertNotSent(fn (Request $request): bool => ! str_starts_with($request->url(), 'https://source.example.test:10000/'));
    Http::assertNotSent(fn (Request $request): bool => ! in_array($request['program'], ['info', 'list-domains'], true));
});

it('rejects another tenant before reading its source credentials or contacting the API', function (): void {
    config(['hosting-migrations.sources.legacy.team_id' => 'other-team', 'hosting-migrations.sources.legacy.credentials_file' => '/missing/protected/file']);
    $this->artisan('hosting:inspect-migration-source legacy')->assertFailed();
    Http::assertNothingSent();
});

it('rejects exposed, malformed or unexpected credential files before API requests', function (string $problem): void {
    match ($problem) {
        'exposed' => chmod($this->credentialFile, 0644),
        'malformed' => file_put_contents($this->credentialFile, '{private-api-password'),
        'redirect-field' => file_put_contents($this->credentialFile, '{"username":"root","password":"private-api-password","url":"https://attacker.test"}'),
        'large' => file_put_contents($this->credentialFile, str_repeat('a', 17000)),
    };
    $this->artisan('hosting:inspect-migration-source legacy')->assertFailed();
    Http::assertNothingSent();
})->with(['exposed', 'malformed', 'redirect-field', 'large']);

it('does not claim an inspection or store upstream credentials when the source rejects authentication', function (): void {
    $this->apiFailure = true;
    $this->artisan('hosting:inspect-migration-source legacy')->expectsOutputToContain('No data was imported')->assertFailed();
    expect(AuditEntry::query()->where('event', 'hosting.migration-source.inspected')->exists())->toBeFalse();
});

it('rejects unsupported providers instead of claiming a completed migration', function (): void {
    config(['hosting-migrations.sources.legacy.provider' => 'cpanel']);
    $this->artisan('hosting:inspect-migration-source legacy')->assertFailed();
    Http::assertNothingSent();
});

it('does not inspect sources bound to suspended or archived destination clusters', function (string $action): void {
    app($action)->execute($this->cluster);
    $this->artisan('hosting:inspect-migration-source legacy')->assertFailed();
    Http::assertNothingSent();
})->with([SuspendCluster::class, ArchiveCluster::class]);

function bindStoredSourceCredential(object $test): NodeCredential
{
    $node = app(RegisterNode::class)->execute(['team_id' => 'team', 'name' => 'Legacy Virtualmin', 'hostname' => 'source.example.test', 'platform' => 'virtualmin8-source']);
    $credential = app(RegisterNodeCredential::class)->execute([
        'team_id' => 'team', 'node_id' => $node->getKey(), 'name' => 'Migration API', 'type' => 'api',
        'username' => 'root', 'secret' => 'private-api-password', 'expires_at' => now()->addHour(),
        'metadata' => ['provider' => 'virtualmin8', 'origin' => 'https://source.example.test:10000'],
    ]);
    config(['hosting-migrations.sources.legacy.credentials_file' => null,
        'hosting-migrations.sources.legacy.node_id' => $node->getKey(), 'hosting-migrations.sources.legacy.credential_id' => $credential->getKey()]);

    return $credential;
}

it('uses encrypted node credentials without copying plaintext into audit or inventory', function (): void {
    $credential = bindStoredSourceCredential($this);
    expect($credential->getRawOriginal('secret'))->not->toContain('private-api-password');
    $this->artisan('hosting:inspect-migration-source legacy')->assertSuccessful();
    $audit = AuditEntry::query()->where('event', 'hosting.migration-source.inspected')->firstOrFail();
    expect(json_encode($audit))->not->toContain('private-api-password');
});

it('rejects revoked, expired, foreign or wrong-origin stored source credentials before requests', function (string $problem): void {
    $credential = bindStoredSourceCredential($this);
    match ($problem) {
        'revoked' => app(RevokeNodeCredential::class)->execute($credential),
        'expired' => app(UpdateNodeCredential::class)->execute($credential, ['expires_at' => now()->subMinute()]),
        'wrong-origin' => app(UpdateNodeCredential::class)->execute($credential, ['metadata' => ['provider' => 'virtualmin8', 'origin' => 'https://another.example.test:10000']]),
        'foreign-team' => config(['hosting-migrations.sources.legacy.team_id' => 'another-team']),
    };
    $this->artisan('hosting:inspect-migration-source legacy')->assertFailed();
    Http::assertNothingSent();
})->with(['revoked', 'expired', 'wrong-origin', 'foreign-team']);

it('does not accept a credential revoked while source inspection is running', function (): void {
    $credential = bindStoredSourceCredential($this);
    $this->afterSourceRequest = function () use ($credential): void {
        if ($credential->fresh()->getAttribute('status') === CredentialStatus::Active) {
            app(RevokeNodeCredential::class)->execute($credential);
        }
    };
    $this->artisan('hosting:inspect-migration-source legacy')->assertFailed();
    expect(AuditEntry::query()->where('event', 'hosting.migration-source.inspected')->exists())->toBeFalse();
});
