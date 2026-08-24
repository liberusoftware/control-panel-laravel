<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Models;

use Illuminate\Database\Eloquent\Model;

final class FileRetention extends Model
{
    protected $table = 'control_panel_file_retention';

    protected $fillable = ['id', 'team_id', 'file_id', 'retention_until', 'policy', 'active'];

    protected function casts(): array
    {
        return ['retention_until' => 'datetime', 'active' => 'boolean'];
    }
}
