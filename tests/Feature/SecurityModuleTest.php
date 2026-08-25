<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Security\Actions\RecordFinding;
use Liberu\ControlPanel\Security\Actions\RecordSecurityResource;
use Liberu\ControlPanel\Security\Actions\ResolveSecurityFinding;
use Liberu\ControlPanel\Security\Actions\StoreSecret;
use Liberu\ControlPanel\Security\Models\ComplianceStatus;
use Liberu\ControlPanel\Security\Models\HardeningControl;
use Liberu\ControlPanel\Security\Models\IntrusionControl;
use Liberu\ControlPanel\Security\Models\MalwareScan;
use Liberu\ControlPanel\Security\Models\MfaRbacPolicy;
use Liberu\ControlPanel\Security\Models\PatchRecord;
use Liberu\ControlPanel\Security\Models\SecurityFinding;
use Liberu\ControlPanel\Security\SecurityServiceProvider;

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
