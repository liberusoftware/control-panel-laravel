<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Files\Actions\RegisterFile;
use Liberu\ControlPanel\Files\Models\FileEntry;
use Liberu\ControlPanel\Files\Queries\ListFiles;

final class FileController
{
    public function index(Request $request, ListFiles $list): JsonResponse
    {
        $files = $list->execute($request->user()?->current_team_id, $request->integer('per_page', 25));

        return response()->json(['data' => $files->through(static fn (FileEntry $file): array => self::resource($file)), 'meta' => ['current_page' => $files->currentPage(), 'per_page' => $files->perPage(), 'total' => $files->total()]]);
    }

    public function store(Request $request, RegisterFile $register): JsonResponse
    {
        $data = $request->validate(['path' => ['required', 'string', 'max:1024'], 'disk' => ['required', 'string', 'max:100'], 'owner_id' => ['nullable', 'string', 'max:255'], 'mime_type' => ['nullable', 'string', 'max:255'], 'size_bytes' => ['nullable', 'integer', 'min:0'], 'metadata' => ['nullable', 'array']]);
        $file = $register->execute(array_merge($data, ['team_id' => $request->user()?->current_team_id]));

        return response()->json(['data' => self::resource($file)], 201);
    }

    private static function resource(FileEntry $file): array
    {
        return ['id' => $file->getKey(), 'type' => 'control-panel-file', 'attributes' => $file->only(['path', 'disk', 'mime_type', 'size_bytes', 'checksum', 'status', 'scanned_at', 'retention_until', 'metadata'])];
    }
}
