<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Security\Actions\RecordFinding;
use Liberu\ControlPanel\Security\Actions\RecordSecurityResource;
use Liberu\ControlPanel\Security\Actions\ResolveSecurityFinding;
use Liberu\ControlPanel\Security\Actions\StoreSecret;
use Liberu\ControlPanel\Security\Actions\UpdateSecurityFinding;
use Liberu\ControlPanel\Security\Models\ComplianceStatus;
use Liberu\ControlPanel\Security\Models\HardeningControl;
use Liberu\ControlPanel\Security\Models\IntrusionControl;
use Liberu\ControlPanel\Security\Models\MalwareScan;
use Liberu\ControlPanel\Security\Models\MfaRbacPolicy;
use Liberu\ControlPanel\Security\Models\PatchRecord;
use Liberu\ControlPanel\Security\Models\SecurityFinding;
use Liberu\ControlPanel\Security\SecurityServiceProvider;
use Liberu\ControlPanel\SecurityApi\SecurityApiServiceProvider;
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
