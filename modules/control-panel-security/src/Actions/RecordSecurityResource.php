<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;

final class RecordSecurityResource
{
    /** @param class-string<Model> $modelClass @param array<string, mixed> $attributes */
    public function execute(string $modelClass, array $attributes): Model
    {
        if (trim((string) ($attributes['team_id'] ?? '')) === '') {
            throw ValidationException::withMessages(['team_id' => 'A team context is required.']);
        }
        return $modelClass::query()->create(array_merge(['id' => (string) Str::uuid()], $attributes));
    }
}
