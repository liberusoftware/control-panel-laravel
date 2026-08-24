<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Actions\CreateDestination;
use Liberu\ControlPanel\Backups\Actions\CreatePolicy;
use Liberu\ControlPanel\Backups\Actions\CreateSchedule;
use Liberu\ControlPanel\Backups\Actions\CreateSnapshot;
use Liberu\ControlPanel\Backups\Actions\RequestRestore;
use Liberu\ControlPanel\Backups\Actions\VerifySnapshot;
use Liberu\ControlPanel\Backups\BackupsServiceProvider;
use Liberu\ControlPanel\Backups\Enums\RestoreStatus;
use Liberu\ControlPanel\Backups\Enums\SnapshotStatus;

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

    expect($destination->toArray())->not->toHaveKey('config')
        ->and($destination->config)->toMatchArray(['secret' => 'hidden'])
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
