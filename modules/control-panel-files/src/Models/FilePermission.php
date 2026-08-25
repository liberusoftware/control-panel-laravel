<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Models;

use Illuminate\Database\Eloquent\Model;

final class FilePermission extends Model
{
    protected $table = 'control_panel_file_permissions';

    protected $fillable = ['id', 'team_id', 'file_id', 'home_directory_id', 'subject_id', 'subject_type', 'mode', 'recursive'];

    protected function casts(): array
    {
        return ['mode' => 'integer', 'recursive' => 'boolean'];
    }
}
