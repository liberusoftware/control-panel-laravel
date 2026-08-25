<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\OsAdapters\Models\SupportMatrixEntry;

final class RecordSupportMatrix
{
    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): SupportMatrixEntry
    {
        foreach (['operating_system', 'version', 'capability'] as $field) {
            if (trim((string) ($attributes[$field] ?? '')) === '') {
                throw ValidationException::withMessages([$field => "The {$field} field is required."]);
            }
        }
        return SupportMatrixEntry::query()->updateOrCreate(
            ['operating_system' => $attributes['operating_system'], 'version' => $attributes['version'], 'capability' => $attributes['capability']],
            ['id' => (string) Str::uuid(), 'supported' => (bool) ($attributes['supported'] ?? false), 'minimum_adapter_version' => $attributes['minimum_adapter_version'] ?? null, 'notes' => $attributes['notes'] ?? null],
        );
    }
}
