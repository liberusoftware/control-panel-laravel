<?php

declare(strict_types=1);
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Certificates\Actions\CheckCertificateExpiry;
use Liberu\ControlPanel\Certificates\Actions\ExpireCertificate;
use Liberu\ControlPanel\Certificates\Actions\IssueCertificate;
use Liberu\ControlPanel\Certificates\Actions\RegisterAcmeAccount;
use Liberu\ControlPanel\Certificates\Actions\RegisterCertificateLifecycle;
use Liberu\ControlPanel\Certificates\Actions\RequestCertificateDeployment;
use Liberu\ControlPanel\Certificates\Actions\RequestCertificateRenewal;
use Liberu\ControlPanel\Certificates\Actions\RevokeCertificate;
use Liberu\ControlPanel\Certificates\CertificatesServiceProvider;
use Liberu\ControlPanel\Certificates\Enums\CertificateStatus;
use Liberu\ControlPanel\CertificatesApi\CertificatesApiServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);
beforeEach(function (): void {
    app()->register(CertificatesServiceProvider::class);
    $this->artisan('migrate');
});
it('supports ACME, deployment, renewal, revocation operations, and expiry alerts', function (): void {
    $account = app(RegisterAcmeAccount::class)->execute(['team_id' => 'team-1', 'email' => 'admin@example.test', 'credentials' => ['account' => 'encrypted']]);
    $a = app(RegisterCertificateLifecycle::class);
    $deployment = $a->execute(['team_id' => 'team-1', 'kind' => 'deployment', 'certificate_id' => 'certificate-1', 'target_type' => 'web-server', 'target_id' => 'server-1', 'status' => 'completed']);
    $renewal = $a->execute(['team_id' => 'team-1', 'kind' => 'renewal', 'certificate_id' => 'certificate-1', 'scheduled_at' => now()->addDays(20)]);
    $expiry = $a->execute(['team_id' => 'team-1', 'kind' => 'expiry', 'certificate_id' => 'certificate-1', 'threshold_days' => 30]);
    expect($account->credentials)->toMatchArray(['account' => 'encrypted'])->and($deployment->status)->toBe('completed')->and($renewal->status)->toBe('queued')->and($expiry->threshold_days)->toBe(30);
});
it('rejects unknown certificate lifecycle operations', function (): void {
    expect(fn () => app(RegisterCertificateLifecycle::class)->execute(['team_id' => 'team-1', 'kind' => 'unknown']))->toThrow(ValidationException::class);
});

it('queues tenant-owned deployment and renewal workflows and records expiry state', function (): void {
    $certificate = app(IssueCertificate::class)->execute([
        'team_id' => 'team-1',
        'domains' => ['example.test'],
        'expires_at' => now()->addDays(10),
    ]);

    $deployment = app(RequestCertificateDeployment::class)->execute($certificate, [
        'target_type' => 'web-server',
        'target_id' => 'server-1',
    ]);
    $renewal = app(RequestCertificateRenewal::class)->execute($certificate);
    $alert = app(CheckCertificateExpiry::class)->execute($certificate, 30);
    $rechecked = app(CheckCertificateExpiry::class)->execute($certificate, 30);

    expect($deployment->status)->toBe('queued')
        ->and($renewal->status)->toBe('queued')
        ->and($alert->status)->toBe('triggered')
        ->and($rechecked->getKey())->toBe($alert->getKey());
});

it('rejects duplicate renewal requests and revokes a certificate through the domain action', function (): void {
    $certificate = app(IssueCertificate::class)->execute(['team_id' => 'team-1', 'domains' => ['example.test']]);
    app(RequestCertificateRenewal::class)->execute($certificate);

    expect(fn () => app(RequestCertificateRenewal::class)->execute($certificate))
        ->toThrow(ValidationException::class);

    $revoked = app(RevokeCertificate::class)->execute($certificate);

    expect($revoked->status->value)->toBe('revoked');
});

it('never serializes certificate private keys', function (): void {
    $certificate = app(IssueCertificate::class)->execute([
        'team_id' => 'team-1', 'domains' => ['secure.example.test'], 'private_key' => 'private-key-material',
    ]);

    expect($certificate->toArray())->not->toHaveKey('private_key')
        ->and($certificate->private_key)->toBe('private-key-material');
});

it('expires a past-dated active certificate and rejects invalid repeats', function (): void {
    $certificate = app(IssueCertificate::class)->execute([
        'team_id' => 'team-1', 'domains' => ['expired.example.test'], 'expires_at' => now()->subMinute(),
    ]);

    $expired = app(ExpireCertificate::class)->execute($certificate);

    expect($expired->status)->toBe(CertificateStatus::Expired)
        ->and(fn () => app(ExpireCertificate::class)->execute($expired))
        ->toThrow(ValidationException::class);
});

it('exposes certificate lifecycle actions through a tenant-scoped API', function (): void {
    app()->register(CertificatesApiServiceProvider::class);
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $certificate = app(IssueCertificate::class)->execute(['team_id' => $team->getKey(), 'domains' => ['example.test']]);
    $otherCertificate = app(IssueCertificate::class)->execute(['team_id' => $otherTeam->getKey(), 'domains' => ['other.test']]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/certificates/'.$certificate->getKey().'/deploy', ['target_type' => 'web-server', 'target_id' => 'server-1'])
        ->assertAccepted()
        ->assertJsonPath('data.certificate_id', $certificate->getKey());

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/certificates/'.$otherCertificate->getKey().'/revoke')
        ->assertNotFound();

    $expiredCertificate = app(IssueCertificate::class)->execute([
        'team_id' => $team->getKey(), 'domains' => ['expired.example.test'], 'expires_at' => now()->subMinute(),
    ]);
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/certificates/'.$expiredCertificate->getKey().'/expire')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'expired');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/certificates/'.$certificate->getKey().'/revoke')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'revoked');
});
