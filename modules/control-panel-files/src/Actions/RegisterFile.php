<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Files\Enums\FileStatus;
use Liberu\ControlPanel\Files\Events\FileRegistered;
use Liberu\ControlPanel\Files\Models\FileEntry;

final readonly class RegisterFile
{
    public function __construct(private Dispatcher $events) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): FileEntry
    {
        $path = trim((string) ($attributes['path'] ?? ''));
        $disk = trim((string) ($attributes['disk'] ?? ''));
        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '..') || $disk === '') {
            throw ValidationException::withMessages(['file' => 'A relative file path and storage disk are required.']);
        }

        return DB::transaction(function () use ($attributes, $path, $disk): FileEntry {
            $file = FileEntry::query()->create(['id' => (string) Str::uuid(), 'team_id' => $attributes['team_id'] ?? null, 'owner_id' => $attributes['owner_id'] ?? null, 'path' => $path, 'disk' => $disk, 'mime_type' => $attributes['mime_type'] ?? null, 'size_bytes' => max((int) ($attributes['size_bytes'] ?? 0), 0), 'checksum' => $attributes['checksum'] ?? null, 'status' => FileStatus::PendingScan, 'retention_until' => $attributes['retention_until'] ?? null, 'metadata' => $attributes['metadata'] ?? []]);
            $this->events->dispatch(new FileRegistered($file));

            return $file;
        });
    }
}
