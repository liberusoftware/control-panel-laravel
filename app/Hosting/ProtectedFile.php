<?php

namespace App\Hosting;

use InvalidArgumentException;

final class ProtectedFile
{
    public static function read(string $path, int $maximumBytes): string
    {
        if (! str_starts_with($path, '/') || $maximumBytes < 1 || $maximumBytes > 8 * 1024 * 1024) {
            throw new InvalidArgumentException('Choose an absolute protected file path and a bounded byte limit.');
        }
        for ($parent = $path; $parent !== '/'; $parent = dirname($parent)) {
            if (is_link($parent)) {
                throw new InvalidArgumentException('Protected files must not traverse symbolic links; mount Kubernetes Secrets with a read-only subPath.');
            }
        }
        $before = @lstat($path);
        $uid = function_exists('posix_geteuid') ? posix_geteuid() : getmyuid();
        $groups = function_exists('posix_getgroups') ? (posix_getgroups() ?: []) : [];
        if (function_exists('posix_getegid')) {
            $groups[] = posix_getegid();
        }
        if ($before === false || ! self::hasPrivatePermissions($before, $uid, $groups) || $before['size'] > $maximumBytes) {
            throw new InvalidArgumentException('Protected files must be private operator-owned files or root-owned Secret files readable only by the operator group, within the byte limit.');
        }
        $input = @fopen($path, 'rb');
        if ($input === false) {
            throw new InvalidArgumentException('The protected file is unreadable.');
        }
        try {
            $opened = fstat($input);
            if ($opened === false || $opened['ino'] !== $before['ino'] || $opened['dev'] !== $before['dev']
                || ! self::hasPrivatePermissions($opened, $uid, $groups)) {
                throw new InvalidArgumentException('The protected file changed or became an unsafe link.');
            }
            $contents = stream_get_contents($input, $maximumBytes + 1);
        } finally {
            fclose($input);
        }
        if ($contents === false || strlen($contents) > $maximumBytes) {
            throw new InvalidArgumentException('The protected file exceeds its byte limit.');
        }

        return $contents;
    }

    /**
     * Root-owned, group-readable Secret mounts are needed by non-root Kubernetes containers.
     * User-owned files may never grant access to their group or other users.
     *
     * @param  array<array-key, int>  $metadata
     * @param  list<int>  $operatorGroups
     */
    public static function hasPrivatePermissions(array $metadata, int $operatorUid, array $operatorGroups): bool
    {
        return ($metadata['mode'] & 0170000) === 0100000 && $metadata['nlink'] === 1 && (
            ($metadata['uid'] === $operatorUid && ($metadata['mode'] & 0077) === 0)
            || ($metadata['uid'] === 0 && in_array($metadata['gid'], $operatorGroups, true) && ($metadata['mode'] & 0037) === 0));
    }
}
