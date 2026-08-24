<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Files\Enums\FileStatus;

final class FileEntry extends Model
{
    use HasUuids;

    protected $table = 'control_panel_files';

    protected $fillable = ['team_id', 'owner_id', 'path', 'disk', 'mime_type', 'size_bytes', 'checksum', 'status', 'scanned_at', 'retention_until', 'metadata'];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer', 'status' => FileStatus::class, 'scanned_at' => 'datetime', 'retention_until' => 'datetime', 'metadata' => 'array'];
    }
}
