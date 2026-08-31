<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Security\Actions\ConfigureFail2ban;
use Liberu\ControlPanel\Security\Actions\RecordFail2banBan;
use Liberu\ControlPanel\Security\Actions\RecordFinding;
use Liberu\ControlPanel\Security\Actions\RecordSecurityResource;
use Liberu\ControlPanel\Security\Actions\ResolveSecurityFinding;
use Liberu\ControlPanel\Security\Actions\StoreSecret;
use Liberu\ControlPanel\Security\Actions\UnbanFail2banBan;
use Liberu\ControlPanel\Security\Actions\UpdateSecurityFinding;
use Liberu\ControlPanel\Security\Models\ComplianceStatus;
use Liberu\ControlPanel\Security\Models\Fail2banBan;
use Liberu\ControlPanel\Security\Models\HardeningControl;
use Liberu\ControlPanel\Security\Models\IntrusionControl;
use Liberu\ControlPanel\Security\Models\MalwareScan;
use Liberu\ControlPanel\Security\Models\MfaRbacPolicy;
use Liberu\ControlPanel\Security\Models\PatchRecord;
use Liberu\ControlPanel\Security\Models\SecurityFinding;
use Liberu\ControlPanel\Security\SecurityServiceProvider;
use Liberu\ControlPanel\SecurityApi\SecurityApiServiceProvider;
use Liberu\ControlPanel\SecurityLivewire\Components\Fail2banInventory;
use Liberu\ControlPanel\SecurityLivewire\Components\FindingInventory;
use Liberu\ControlPanel\SecurityLivewire\SecurityLivewireServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(SecurityServiceProvider::class);
    $this->artisan('migrate');
});

it('records hardening, patching, MFA/RBAC, malware, intrusion, and compliance state', function (): void {
    $record = app(RecordSecurityResource::class);
    $common = ['team_id' => 'team-1', 'subject_type' => 'node', 'subject_id' => 'node-1'];
    $hardening = $record->execute(HardeningControl::class, $common + ['control' => 'ssh-root-login', 'desired' => false, 'observed' => false, 'status' => 'pass']);
    $patch = $record->execute(PatchRecord::class, $common + ['package' => 'openssl', 'target_version' => '3.2', 'severity' => 'high', 'status' => 'available']);
    $policy = $record->execute(MfaRbacPolicy::class, $common + ['mfa_required' => true, 'roles' => ['admin'], 'status' => 'active']);
    $malware = $record->execute(MalwareScan::class, $common + ['status' => 'clean', 'scanner' => 'clamav']);
    $intrusion = $record->execute(IntrusionControl::class, $common + ['kind' => 'login', 'action' => 'block', 'threshold' => 5, 'window_seconds' => 300]);
    $compliance = $record->execute(ComplianceStatus::class, ['team_id' => 'team-1', 'framework' => 'cis', 'control' => '1.1', 'status' => 'pass', 'score' => 100]);

    expect($hardening->observed)->toBeFalse()->and($patch->severity)->toBe('high')->and($policy->mfa_required)->toBeTrue()->and($malware->scanner)->toBe('clamav')->and($intrusion->threshold)->toBe(5)->and($compliance->score)->toBe(100);
});

it('encrypts secrets and rejects missing secret values', function (): void {
    $secret = app(StoreSecret::class)->execute(['team_id' => 'team-1', 'name' => 'provider-key', 'value' => 'super-secret']);
    expect($secret->toArray())->not->toHaveKey('value')->and($secret->value)->toBe('super-secret');

    expect(fn () => app(StoreSecret::class)->execute(['team_id' => 'team-1', 'name' => 'empty', 'value' => '']))
        ->toThrow(ValidationException::class);
});

it('manages tenant-scoped Fail2ban jails and ban history', function (): void {
    $setting = app(ConfigureFail2ban::class)->execute(['team_id' => 'team-1', 'jail_name' => 'sshd', 'max_retry' => 3, 'whitelist_ips' => ['192.0.2.1']]);
    $ban = app(RecordFail2banBan::class)->execute($setting, ['ip_address' => '198.51.100.10', 'reason' => 'Repeated authentication failures']);

    expect($setting->max_retry)->toBe(3)
        ->and($setting->bans()->count())->toBe(1)
        ->and($ban->isActive())->toBeTrue()
        ->and(app(UnbanFail2banBan::class)->execute($ban)->unbanned_at)->not->toBeNull();
    expect(fn () => app(RecordFail2banBan::class)->execute($setting, ['ip_address' => '192.0.2.1']))->toThrow(ValidationException::class)
        ->and(fn () => app(UnbanFail2banBan::class)->execute($ban->fresh()))->toThrow(ValidationException::class);
});

it('exposes Fail2ban jail and ban controls through the tenant API', function (): void {
    app()->register(SecurityApiServiceProvider::class);
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(ConfigureFail2ban::class)->execute(['team_id' => $otherTeam->getKey(), 'jail_name' => 'postfix']);

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/security/fail2ban', ['jail_name' => 'sshd', 'whitelist_ips' => ['192.0.2.1']])
        ->assertCreated()->assertJsonPath('data.attributes.jail_name', 'sshd');
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/security/fail2ban/sshd/bans', ['ip_address' => '198.51.100.11'])
        ->assertCreated()->assertJsonPath('data.attributes.ip_address', '198.51.100.11');
    $this->actingAs($user, 'sanctum')->getJson('/api/v1/control-panel/security/fail2ban/'.$foreign->jail_name.'/bans')->assertNotFound();
    $ban = Fail2banBan::query()->where('team_id', $team->getKey())->firstOrFail();
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/security/fail2ban/bans/'.$ban->getKey().'/unban')->assertOk()->assertJsonPath('data.attributes.unbanned_at', fn ($value): bool => $value !== null);
});

it('lists security resource families without leaking secret values', function (): void {
    app()->register(SecurityApiServiceProvider::class);
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    app(RecordSecurityResource::class)->execute(PatchRecord::class, [
        'team_id' => $team->getKey(), 'subject_type' => 'node', 'subject_id' => 'node-1',
        'package' => 'openssl', 'target_version' => '3.2', 'severity' => 'high', 'status' => 'available',
    ]);
    app(StoreSecret::class)->execute(['team_id' => $team->getKey(), 'name' => 'provider-key', 'value' => 'never-return-this']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/control-panel/security/patches')
        ->assertOk()
        ->assertJsonPath('data.0.attributes.package', 'openssl');
    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/control-panel/security/secrets')
        ->assertOk()
        ->assertJsonPath('data.0.attributes.name', 'provider-key')
        ->assertDontSee('never-return-this')
        ->assertJsonMissingPath('data.0.attributes.value');
});

it('scopes Fail2ban unban controls to the current team in Livewire', function (): void {
    app()->register(SecurityLivewireServiceProvider::class);
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreignSetting = app(ConfigureFail2ban::class)->execute(['team_id' => $otherTeam->getKey(), 'jail_name' => 'sshd']);
    $foreignBan = app(RecordFail2banBan::class)->execute($foreignSetting, ['ip_address' => '198.51.100.20']);

    $this->actingAs($user);
    $inventory = app(Fail2banInventory::class);
    expect(fn () => $inventory->unban($foreignBan->getKey(), app(UnbanFail2banBan::class)))->toThrow(ModelNotFoundException::class);
});

it('resolves only open security findings through the lifecycle action', function (): void {
    $finding = app(RecordFinding::class)->execute([
        'team_id' => 'team-1', 'subject_type' => 'node', 'subject_id' => 'node-1',
        'code' => 'weak-ssh', 'severity' => 'high', 'summary' => 'SSH hardening is required',
    ]);

    expect(app(ResolveSecurityFinding::class)->execute($finding)->status)->toBe('resolved');
    expect(fn () => app(ResolveSecurityFinding::class)->execute($finding->fresh()))
        ->toThrow(ValidationException::class);
    expect(SecurityFinding::query()->find($finding->getKey())->status)->toBe('resolved');
});

it('requires tenant context when recording security findings', function (): void {
    expect(fn () => app(RecordFinding::class)->execute([
        'subject_type' => 'node', 'subject_id' => 'node-1',
        'code' => 'weak-ssh', 'severity' => 'high', 'summary' => 'SSH hardening is required',
    ]))->toThrow(ValidationException::class);
});

it('updates open findings and protects resolved findings', function (): void {
    $finding = app(RecordFinding::class)->execute(['team_id' => 'team-1', 'subject_type' => 'node', 'subject_id' => 'node-1', 'code' => 'weak-ssh', 'severity' => 'high', 'summary' => 'SSH hardening is required']);
    $updated = app(UpdateSecurityFinding::class)->execute($finding, ['severity' => 'critical', 'summary' => 'Immediate SSH hardening is required']);

    expect($updated->severity)->toBe('critical')->and($updated->summary)->toBe('Immediate SSH hardening is required');
    app(ResolveSecurityFinding::class)->execute($updated);
    expect(fn () => app(UpdateSecurityFinding::class)->execute($updated, ['summary' => 'Blocked']))->toThrow(ValidationException::class);
});

it('updates only a current-team finding through API and Livewire', function (): void {
    app()->register(SecurityApiServiceProvider::class);
    app()->register(SecurityLivewireServiceProvider::class);
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(RecordFinding::class)->execute(['team_id' => $otherTeam->getKey(), 'subject_type' => 'node', 'subject_id' => 'foreign', 'code' => 'weak-ssh', 'severity' => 'high', 'summary' => 'Foreign finding']);
    $owned = app(RecordFinding::class)->execute(['team_id' => $team->getKey(), 'subject_type' => 'node', 'subject_id' => 'owned', 'code' => 'weak-ssh', 'severity' => 'high', 'summary' => 'Owned finding']);

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/security/'.$foreign->getKey(), ['severity' => 'critical'])->assertNotFound();
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/security/'.$owned->getKey(), ['severity' => 'critical'])->assertOk()->assertJsonPath('data.attributes.severity', 'critical');

    $inventory = app(FindingInventory::class);
    expect(fn () => $inventory->update($foreign->getKey(), ['summary' => 'Blocked'], app(UpdateSecurityFinding::class)))->toThrow(ModelNotFoundException::class);
    $inventory->update($owned->getKey(), ['summary' => 'Updated finding'], app(UpdateSecurityFinding::class));

    expect($owned->refresh()->summary)->toBe('Updated finding');
});
