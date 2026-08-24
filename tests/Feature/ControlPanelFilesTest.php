<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Files\Actions\MarkFileScanned;
use Liberu\ControlPanel\Files\Actions\RegisterFile;
use Liberu\ControlPanel\Files\Enums\FileStatus;
use Liberu\ControlPanel\Files\Events\FileRegistered;
use Liberu\ControlPanel\Files\Events\FileScanned;
use Liberu\ControlPanel\Files\FilesServiceProvider;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(FilesServiceProvider::class);
    $this->artisan('migrate');
});

it('registers a pending-scan file and dispatches an event', function (): void {
    Event::fake();
    $file = app(RegisterFile::class)->execute(['team_id' => 'team-1', 'path' => 'home/index.html', 'disk' => 's3', 'size_bytes' => 42]);

    expect($file->status)->toBe(FileStatus::PendingScan)->and($file->path)->toBe('home/index.html');
    Event::assertDispatched(FileRegistered::class);
});

it('marks a clean file available and an unsafe file quarantined', function (): void {
    $clean = app(RegisterFile::class)->execute(['path' => 'home/clean.txt', 'disk' => 'local']);
    $unsafe = app(RegisterFile::class)->execute(['path' => 'home/unsafe.bin', 'disk' => 'local']);

    Event::fake();
    expect(app(MarkFileScanned::class)->execute($clean, true)->status)->toBe(FileStatus::Available)
        ->and(app(MarkFileScanned::class)->execute($unsafe, false)->status)->toBe(FileStatus::Quarantined);
    Event::assertDispatched(FileScanned::class);
});

it('rejects absolute and traversal paths', function (): void {
    expect(fn () => app(RegisterFile::class)->execute(['path' => '/etc/passwd', 'disk' => 'local']))->toThrow(ValidationException::class)
        ->and(fn () => app(RegisterFile::class)->execute(['path' => '../secret', 'disk' => 'local']))->toThrow(ValidationException::class);
});
