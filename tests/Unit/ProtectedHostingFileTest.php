<?php

use App\Hosting\ProtectedFile;

it('reads bounded operator-private files', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'hosting-private-');
    try {
        chmod($path, 0600);
        file_put_contents($path, 'private-value');
        expect(ProtectedFile::read($path, 64))->toBe('private-value');
        expect(fn () => ProtectedFile::read($path, 4))->toThrow(InvalidArgumentException::class);
        chmod($path, 0644);
        expect(fn () => ProtectedFile::read($path, 64))->toThrow(InvalidArgumentException::class);
    } finally {
        unlink($path);
    }
});

it('enforces host-file privacy and non-root Kubernetes Secret mount permissions', function (int $owner, int $group, int $mode, bool $allowed): void {
    $stat = ['uid' => $owner, 'gid' => $group, 'mode' => 0100000 | $mode, 'nlink' => 1];
    expect(ProtectedFile::hasPrivatePermissions($stat, 1000, [1000, 2000]))->toBe($allowed);
})->with([
    'private owner' => [1000, 1000, 0600, true],
    'read-only owner' => [1000, 1000, 0400, true],
    'exposed owner file' => [1000, 1000, 0640, false],
    'root-owned primary group Secret' => [0, 1000, 0440, true],
    'root-owned supplementary group Secret' => [0, 2000, 0440, true],
    'unrelated group' => [0, 3000, 0440, false],
    'world-readable Secret' => [0, 1000, 0444, false],
    'group-writable Secret' => [0, 1000, 0460, false],
    'foreign owner' => [2000, 1000, 0400, false],
]);

it('rejects symbolic and hard-linked credential files', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'hosting-private-');
    $symbolic = $path.'-symbolic';
    $hard = $path.'-hard';
    try {
        chmod($path, 0600);
        file_put_contents($path, 'private-value');
        symlink($path, $symbolic);
        expect(fn () => ProtectedFile::read($symbolic, 64))->toThrow(InvalidArgumentException::class);
        link($path, $hard);
        expect(fn () => ProtectedFile::read($path, 64))->toThrow(InvalidArgumentException::class);
    } finally {
        @unlink($symbolic);
        @unlink($hard);
        unlink($path);
    }
});
