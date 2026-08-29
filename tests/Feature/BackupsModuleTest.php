<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Actions\CreateDestination;
use Liberu\ControlPanel\Backups\Actions\CreatePolicy;
use Liberu\ControlPanel\Backups\Actions\CreateSchedule;
use Liberu\ControlPanel\Backups\Actions\CreateSnapshot;
use Liberu\ControlPanel\Backups\Actions\DeleteDestination;
use Liberu\ControlPanel\Backups\Actions\DeletePolicy;
use Liberu\ControlPanel\Backups\Actions\DeleteSchedule;
use Liberu\ControlPanel\Backups\Actions\RequestRestore;
use Liberu\ControlPanel\Backups\Actions\UpdateDestination;
use Liberu\ControlPanel\Backups\Actions\UpdatePolicy;
use Liberu\ControlPanel\Backups\Actions\UpdateSchedule;
use Liberu\ControlPanel\Backups\Actions\VerifySnapshot;
use Liberu\ControlPanel\Backups\BackupsServiceProvider;
use Liberu\ControlPanel\Backups\Enums\RestoreStatus;
use Liberu\ControlPanel\Backups\Enums\SnapshotStatus;
use Liberu\ControlPanel\Backups\Models\BackupEncryption;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(BackupsServiceProvider::class);
    $this->artisan('migrate');
});

it('owns encrypted destinations, policies, schedules, snapshots, verification, and restore requests', function (): void {
    $destination = app(CreateDestination::class)->execute(['team_id' => 'team-1', 'name' => 'S3', 'driver' => 's3', 'config' => ['secret' => 'hidden']]);
    $policy = app(CreatePolicy::class)->execute(['team_id' => 'team-1', 'name' => 'Nightly', 'storage_driver' => $destination->driver]);
    $schedule = app(CreateSchedule::class)->execute($policy, '0 2 * * *');
    $snapshot = app(CreateSnapshot::class)->execute($policy, ['location' => 's3://bucket/nightly']);
    $verified = app(VerifySnapshot::class)->execute($snapshot, 'sha256:abc');
    $restore = app(RequestRestore::class)->execute($verified, 'team-1', 'node-1');
    $encryption = BackupEncryption::query()->create(['id' => (string) Str::uuid(), 'team_id' => 'team-1', 'policy_id' => $policy->getKey(), 'algorithm' => 'aes-256-gcm', 'key_reference' => 'secret-key-reference', 'active' => true]);

    expect($destination->toArray())->not->toHaveKey('config')
        ->and($destination->config)->toMatchArray(['secret' => 'hidden'])
        ->and($encryption->toArray())->not->toHaveKey('key_reference')
        ->and($encryption->key_reference)->toBe('secret-key-reference')
        ->and($schedule->cron)->toBe('0 2 * * *')
        ->and($verified->status)->toBe(SnapshotStatus::Verified)
        ->and($restore->status)->toBe(RestoreStatus::Queued);
});

it('rejects unsupported destinations and malformed schedules', function (): void {
    expect(fn () => app(CreateDestination::class)->execute(['team_id' => 'team-1', 'name' => 'Bad', 'driver' => 'unknown']))
        ->toThrow(ValidationException::class);
    $policy = app(CreatePolicy::class)->execute(['team_id' => 'team-1', 'name' => 'Policy', 'storage_driver' => 'local']);
    expect(fn () => app(CreateSchedule::class)->execute($policy, 'nightly'))
        ->toThrow(ValidationException::class);
});

it('updates and deletes backup policies through domain actions', function (): void {
    $policy = app(CreatePolicy::class)->execute(['team_id' => 'team-1', 'name' => 'Nightly', 'storage_driver' => 'local']);

    $updated = app(UpdatePolicy::class)->execute($policy, ['name' => 'Hourly', 'storage_driver' => 's3', 'retention_days' => 14, 'encrypted' => false, 'active' => false]);

    expect($updated->name)->toBe('Hourly')
        ->and($updated->storage_driver)->toBe('s3')
        ->and($updated->retention_days)->toBe(14)
        ->and($updated->encrypted)->toBeFalse()
        ->and($updated->active)->toBeFalse();

    app(DeletePolicy::class)->execute($updated);

    expect($policy->newQuery()->whereKey($policy->getKey())->exists())->toBeFalse();
});

it('updates and deletes encrypted backup destinations through domain actions', function (): void {
    $destination = app(CreateDestination::class)->execute(['team_id' => 'team-1', 'name' => 'Local', 'driver' => 'local', 'config' => ['path' => '/backups']]);

    $updated = app(UpdateDestination::class)->execute($destination, ['name' => 'Archive', 'driver' => 's3', 'retention_days' => 90, 'config' => ['bucket' => 'archive']]);

    expect($updated->name)->toBe('Archive')
        ->and($updated->driver)->toBe('s3')
        ->and($updated->retention_days)->toBe(90)
        ->and($updated->config)->toMatchArray(['bucket' => 'archive'])
        ->and($updated->toArray())->not->toHaveKey('config');

    app(DeleteDestination::class)->execute($updated);

    expect($destination->newQuery()->whereKey($destination->getKey())->exists())->toBeFalse();
});

it('updates and deletes backup schedules through domain actions', function (): void {
    $policy = app(CreatePolicy::class)->execute(['team_id' => 'team-1', 'name' => 'Nightly', 'storage_driver' => 'local']);
    $schedule = app(CreateSchedule::class)->execute($policy, '0 2 * * *');

    $updated = app(UpdateSchedule::class)->execute($schedule, ['cron' => '0 4 * * *', 'timezone' => 'Europe/Amsterdam', 'active' => false]);

    expect($updated->cron)->toBe('0 4 * * *')
        ->and($updated->timezone)->toBe('Europe/Amsterdam')
        ->and($updated->active)->toBeFalse();

    app(DeleteSchedule::class)->execute($updated);

    expect($schedule->newQuery()->whereKey($schedule->getKey())->exists())->toBeFalse();
});
