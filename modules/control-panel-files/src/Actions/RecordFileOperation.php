<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Files\Models\FileOperation;

final class RecordFileOperation
{
    public function execute(array $a): FileOperation
    {
        $op = (string) ($a['operation'] ?? '');
        if (! in_array($op, ['copy', 'move', 'delete', 'scan', 'restore'], true)) {
            throw ValidationException::withMessages(['operation' => 'Unsupported file operation.']);
        }

        return FileOperation::query()->create(['id' => (string) Str::uuid(), 'team_id' => $a['team_id'] ?? null, 'file_id' => $a['file_id'] ?? null, 'operation' => $op, 'status' => $a['status'] ?? 'queued', 'details' => $a['details'] ?? []]);
    }
}
