<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class FilesystemMount extends Model
{
    use HasUuids;

    protected $table = 'control_panel_filesystem_mounts';

    protected $fillable = ['team_id', 'node_id', 'device', 'mount_path', 'filesystem', 'size_bytes', 'free_bytes', 'options', 'mounted'];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer', 'free_bytes' => 'integer', 'options' => 'array', 'mounted' => 'bool'];
    }
}
