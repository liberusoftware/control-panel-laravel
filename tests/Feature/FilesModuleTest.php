<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Files\Actions\CreateHomeDirectory;
use Liberu\ControlPanel\Files\Actions\CreateSftpAccount;
use Liberu\ControlPanel\Files\Actions\GrantFilePermission;
use Liberu\ControlPanel\Files\Actions\RegisterFile;
use Liberu\ControlPanel\Files\Actions\SetFileRetention;
use Liberu\ControlPanel\Files\FilesServiceProvider;
use Liberu\ControlPanel\Files\Queries\ListFiles;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(FilesServiceProvider::class);
    $this->artisan('migrate');
});

it('supports tenant-scoped home directories, permissions, SFTP, quotas, and retention', function (): void {
    $home = app(CreateHomeDirectory::class)->execute(['team_id' => 'team-1', 'owner_id' => 'account-1', 'path' => '/srv/accounts/account-1']);
    $permission = app(GrantFilePermission::class)->execute(['team_id' => 'team-1', 'home_directory_id' => $home->id, 'subject_id' => 'account-1', 'mode' => 750, 'recursive' => true]);
    $sftp = app(CreateSftpAccount::class)->execute(['team_id' => 'team-1', 'owner_id' => 'account-1', 'username' => 'deploy', 'password' => 'long-enough-secret']);
    $retention = app(SetFileRetention::class)->execute(['team_id' => 'team-1', 'file_id' => 'file-1', 'retention_until' => now()->addDays(30)]);
    expect($home->path)->toBe('/srv/accounts/account-1')->and($permission->mode)->toBe(750)->and($sftp->password)->toBe('long-enough-secret')->and($retention->active)->toBeTrue();
});

it('rejects unsafe paths, weak SFTP credentials, and invalid permissions', function (): void {
    expect(fn () => app(CreateHomeDirectory::class)->execute(['team_id' => 'team-1', 'path' => '../escape']))->toThrow(ValidationException::class);
    expect(fn () => app(CreateSftpAccount::class)->execute(['team_id' => 'team-1', 'username' => 'deploy']))->toThrow(ValidationException::class);
    expect(fn () => app(GrantFilePermission::class)->execute(['team_id' => 'team-1', 'subject_id' => 'a', 'mode' => 999]))->toThrow(ValidationException::class);
});

it('searches files within the current team only', function (): void {
    app(RegisterFile::class)->execute(['team_id' => 'team-1', 'path' => 'home/report.pdf', 'disk' => 'local', 'mime_type' => 'application/pdf']);
    app(RegisterFile::class)->execute(['team_id' => 'team-1', 'path' => 'home/photo.jpg', 'disk' => 'local', 'mime_type' => 'image/jpeg']);
    app(RegisterFile::class)->execute(['team_id' => 'team-2', 'path' => 'home/report.pdf', 'disk' => 'local', 'mime_type' => 'application/pdf']);

    $files = app(ListFiles::class)->execute('team-1', 25, 'report');

    expect($files->total())->toBe(1)
        ->and($files->first()->path)->toBe('home/report.pdf');
});
