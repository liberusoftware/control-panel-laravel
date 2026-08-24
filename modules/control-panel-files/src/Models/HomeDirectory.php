<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Models;

use Illuminate\Database\Eloquent\Model;

final class HomeDirectory extends Model
{
    protected $table = 'control_panel_file_home_directories';

    protected $fillable = ['id', 'team_id', 'owner_id', 'path', 'disk', 'mode', 'status', 'metadata'];

    protected function casts(): array
    {
        return ['mode' => 'integer', 'metadata' => 'array'];
    }
}
