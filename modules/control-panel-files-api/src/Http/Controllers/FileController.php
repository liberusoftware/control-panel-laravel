<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Files\Actions\CreateHomeDirectory;
use Liberu\ControlPanel\Files\Actions\CreateSftpAccount;
use Liberu\ControlPanel\Files\Actions\DeleteFile;
use Liberu\ControlPanel\Files\Actions\DeleteSftpAccount;
use Liberu\ControlPanel\Files\Actions\GrantFilePermission;
use Liberu\ControlPanel\Files\Actions\RecordFileOperation;
use Liberu\ControlPanel\Files\Actions\RegenerateSftpKeyPair;
use Liberu\ControlPanel\Files\Actions\RegisterFile;
use Liberu\ControlPanel\Files\Actions\SetFileQuota;
use Liberu\ControlPanel\Files\Actions\SetFileRetention;
use Liberu\ControlPanel\Files\Models\FileEntry;
use Liberu\ControlPanel\Files\Models\SftpAccount;
use Liberu\ControlPanel\Files\Queries\ListFiles;

final class FileController
{
    public function index(Request $request, ListFiles $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $files = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $files->through(static fn (FileEntry $file): array => self::resource($file)), 'meta' => ['current_page' => $files->currentPage(), 'per_page' => $files->perPage(), 'total' => $files->total()]]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = FileEntry::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => self::resource($item)]);
    }

    public function delete(Request $request, string $id, DeleteFile $delete): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $file = FileEntry::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();
        $delete->execute($file);

        return response()->json(['data' => self::resource($file)]);
    }

    public function store(Request $request, RegisterFile $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['path' => ['required', 'string', 'max:1024'], 'disk' => ['required', 'string', 'max:100'], 'owner_id' => ['nullable', 'string', 'max:255'], 'mime_type' => ['nullable', 'string', 'max:255'], 'size_bytes' => ['nullable', 'integer', 'min:0'], 'metadata' => ['nullable', 'array']]);
        $file = $register->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($file)], 201);
    }

    public function operation(Request $request, RecordFileOperation $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['file_id' => ['nullable', 'uuid'], 'operation' => ['required', 'in:copy,move,delete,scan,restore'], 'status' => ['nullable', 'in:queued,running,completed,failed'], 'details' => ['nullable', 'array']]);
        $item = $record->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-file-operation', 'attributes' => $item->only(['file_id', 'operation', 'status', 'details'])]], 201);
    }

    public function home(Request $request, CreateHomeDirectory $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['owner_id' => ['nullable', 'string', 'max:255'], 'path' => ['required', 'string', 'max:1024'], 'disk' => ['nullable', 'string', 'max:100'], 'mode' => ['nullable', 'integer', 'min:0', 'max:777'], 'metadata' => ['nullable', 'array']]);
        $item = $create->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-home-directory', 'attributes' => $item->only(['owner_id', 'path', 'disk', 'mode', 'status', 'metadata'])]], 201);
    }

    public function permission(Request $request, GrantFilePermission $grant): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['file_id' => ['nullable', 'uuid'], 'home_directory_id' => ['nullable', 'uuid'], 'subject_id' => ['required', 'string', 'max:255'], 'subject_type' => ['nullable', 'string', 'max:100'], 'mode' => ['required', 'integer', 'min:0', 'max:777'], 'recursive' => ['sometimes', 'boolean']]);
        $item = $grant->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-file-permission', 'attributes' => $item->only(['file_id', 'home_directory_id', 'subject_id', 'subject_type', 'mode', 'recursive'])]], 201);
    }

    public function sftp(Request $request, CreateSftpAccount $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['owner_id' => ['nullable', 'string', 'max:255'], 'username' => ['required', 'string', 'max:32'], 'password' => ['nullable', 'string', 'min:12'], 'home_directory' => ['nullable', 'string', 'max:1024'], 'quota_mb' => ['nullable', 'integer', 'min:0'], 'bandwidth_limit_mb' => ['nullable', 'integer', 'min:0'], 'ssh_public_key' => ['nullable', 'string', 'max:8192'], 'ssh_private_key' => ['nullable', 'string', 'max:16384'], 'ssh_key_type' => ['nullable', 'string', 'max:32'], 'ssh_key_bits' => ['nullable', 'integer', 'min:2048']]);
        $item = $create->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-sftp-account', 'attributes' => $item->only(['owner_id', 'username', 'home_directory', 'quota_mb', 'bandwidth_limit_mb', 'active', 'ssh_key_auth_enabled'])]], 201);
    }

    public function deleteSftp(Request $request, string $id, DeleteSftpAccount $delete): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $account = SftpAccount::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();
        $delete->execute($account);

        return response()->json(status: 204);
    }

    public function regenerateSftpKeys(Request $request, string $id, RegenerateSftpKeyPair $regenerate): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $account = SftpAccount::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate(['ssh_key_bits' => ['sometimes', 'integer', 'in:2048,4096']]);
        $keys = $regenerate->execute($account, isset($data['ssh_key_bits']) ? (int) $data['ssh_key_bits'] : null);

        return response()->json(['data' => [
            'id' => $account->getKey(),
            'type' => 'control-panel-sftp-key-pair',
            'attributes' => ['username' => $account->username, 'public_key' => $keys['public_key'], 'private_key' => $keys['private_key'], 'one_time_private_key' => true],
        ]], 201);
    }

    public function retention(Request $request, SetFileRetention $set): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['file_id' => ['required', 'uuid'], 'retention_until' => ['required', 'date'], 'policy' => ['nullable', 'string', 'max:100']]);
        $item = $set->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-file-retention', 'attributes' => $item->only(['file_id', 'retention_until', 'policy', 'active'])]], 201);
    }

    public function quota(Request $request, SetFileQuota $set): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['owner_id' => ['nullable', 'string', 'max:255'], 'limit_bytes' => ['required', 'integer', 'min:0'], 'used_bytes' => ['sometimes', 'integer', 'min:0'], 'files_count' => ['sometimes', 'integer', 'min:0']]);
        $item = $set->execute(array_merge($data, ['team_id' => (string) $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-file-quota', 'attributes' => $item->only(['owner_id', 'limit_bytes', 'used_bytes', 'files_count'])]], 201);
    }

    private static function resource(FileEntry $file): array
    {
        return ['id' => $file->getKey(), 'type' => 'control-panel-file', 'attributes' => $file->only(['path', 'disk', 'mime_type', 'size_bytes', 'checksum', 'status', 'scanned_at', 'retention_until', 'metadata'])];
    }
}
