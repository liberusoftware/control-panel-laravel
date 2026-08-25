<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\OsAdapters\Models\FilesystemMount;
use Liberu\ControlPanel\OsAdapters\Models\OsPackage;
use Liberu\ControlPanel\OsAdapters\Models\OsService;
use Liberu\ControlPanel\OsAdapters\Models\OsUser;
use Liberu\ControlPanel\OsAdapters\Models\PackageRepository;

final class RecordOsResource
{
    /** @param class-string<Model> $modelClass @param array<string, mixed> $attributes */
    public function execute(string $modelClass, array $attributes): Model
    {
        foreach (['team_id', 'node_id'] as $field) {
            if (trim((string) ($attributes[$field] ?? '')) === '') {
                throw ValidationException::withMessages([$field => "The {$field} field is required."]);
            }
        }
        $key = match ($modelClass) {
            OsPackage::class,
            OsService::class,
            PackageRepository::class => ['name' => $attributes['name'] ?? null],
            OsUser::class => ['username' => $attributes['username'] ?? null],
            FilesystemMount::class => ['mount_path' => $attributes['mount_path'] ?? null],
            default => ['id' => $attributes['id'] ?? (string) Str::uuid()],
        };

        return $modelClass::query()->updateOrCreate(
            array_merge(['team_id' => $attributes['team_id'], 'node_id' => $attributes['node_id']], $key),
            array_merge(['id' => (string) Str::uuid()], $attributes),
        );
    }
}
