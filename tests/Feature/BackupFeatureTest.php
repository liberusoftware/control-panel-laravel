<?php

declare(strict_types=1);
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Actions\RecordBackupFeature;
use Liberu\ControlPanel\Backups\BackupsServiceProvider;

uses(RefreshDatabase::class);
beforeEach(function (): void {
    app()->register(BackupsServiceProvider::class);
    $this->artisan('migrate');
});
it('supports application-consistent executions, encryption, and off-site transfers', function (): void {
    $a = app(RecordBackupFeature::class);
    $execution = $a->execute(['team_id' => 'team-1', 'kind' => 'execution', 'policy_id' => 'policy-1', 'type' => 'database', 'consistency' => 'application-consistent']);
    $encryption = $a->execute(['team_id' => 'team-1', 'kind' => 'encryption', 'policy_id' => 'policy-1', 'algorithm' => 'aes-256-gcm', 'key_reference' => 'kms/key-1']);
    $offsite = $a->execute(['team_id' => 'team-1', 'kind' => 'offsite', 'snapshot_id' => 'snapshot-1', 'destination_id' => 'destination-1']);
    expect($execution->consistency)->toBe('application-consistent')->and($encryption->key_reference)->toBe('kms/key-1')->and($offsite->status)->toBe('queued');
});
it('rejects unknown backup features', function (): void {
    expect(fn () => app(RecordBackupFeature::class)->execute(['team_id' => 'team-1', 'kind' => 'unknown']))->toThrow(ValidationException::class);
});
