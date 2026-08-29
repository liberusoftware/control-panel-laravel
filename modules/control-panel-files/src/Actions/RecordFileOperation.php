<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Files\Models\FileEntry;
use Liberu\ControlPanel\Files\Models\FileOperation;

final class RecordFileOperation
{
    public function execute(array $a): FileOperation
    {
        $teamId = trim((string) ($a['team_id'] ?? ''));
        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A team is required.']);
        }

        $op = (string) ($a['operation'] ?? '');
        if (! in_array($op, ['copy', 'move', 'delete', 'scan', 'restore'], true)) {
            throw ValidationException::withMessages(['operation' => 'Unsupported file operation.']);
        }

        $fileId = $a['file_id'] ?? null;
        if ($fileId !== null && ! FileEntry::query()->whereKey($fileId)->where('team_id', $teamId)->exists()) {
            abort(404);
        }

        return FileOperation::query()->create(['id' => (string) Str::uuid(), 'team_id' => $teamId, 'file_id' => $fileId, 'operation' => $op, 'status' => $a['status'] ?? 'queued', 'details' => $a['details'] ?? []]);
    }
}
