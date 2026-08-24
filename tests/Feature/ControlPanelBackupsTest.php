<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Actions\CreatePolicy;
use Liberu\ControlPanel\Backups\Actions\CreateSnapshot;
use Liberu\ControlPanel\Backups\Actions\VerifySnapshot;
use Liberu\ControlPanel\Backups\BackupsServiceProvider;
use Liberu\ControlPanel\Backups\Enums\SnapshotStatus;
use Liberu\ControlPanel\Backups\Events\SnapshotCreated;
use Liberu\ControlPanel\Backups\Events\SnapshotVerified;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(BackupsServiceProvider::class);
    $this->artisan('migrate');
});

it('creates a policy with encrypted storage configuration', function (): void {
    $policy = app(CreatePolicy::class)->execute(['team_id' => 'team-1', 'name' => 'Nightly', 'storage_driver' => 's3', 'storage_config' => ['secret' => 'hidden']]);

    expect($policy->storage_config)->toBe(['secret' => 'hidden'])->and($policy->encrypted)->toBeTrue();
});

it('creates and verifies a snapshot with after-commit events', function (): void {
    Event::fake();
    $policy = app(CreatePolicy::class)->execute(['name' => 'Nightly', 'storage_driver' => 'local']);
    $snapshot = app(CreateSnapshot::class)->execute($policy);

    expect($snapshot->status)->toBe(SnapshotStatus::Queued);
    Event::assertDispatched(SnapshotCreated::class);
    $verified = app(VerifySnapshot::class)->execute($snapshot, 'sha256:abc');
    expect($verified->status)->toBe(SnapshotStatus::Verified)->and($verified->verified_at)->not->toBeNull();
    Event::assertDispatched(SnapshotVerified::class);
});

it('rejects inactive policies and incomplete policies', function (): void {
    expect(fn () => app(CreatePolicy::class)->execute(['name' => '', 'storage_driver' => 'local']))->toThrow(ValidationException::class);
    $policy = app(CreatePolicy::class)->execute(['name' => 'Nightly', 'storage_driver' => 'local']);
    $policy->update(['active' => false]);

    expect(fn () => app(CreateSnapshot::class)->execute($policy))->toThrow(ValidationException::class);
});
