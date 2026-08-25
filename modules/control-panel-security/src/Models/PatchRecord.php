<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class PatchRecord extends Model
{
    use HasUuids;

    protected $table = 'control_panel_patch_records';

    protected $fillable = ['team_id', 'subject_type', 'subject_id', 'package', 'current_version', 'target_version', 'severity', 'status', 'published_at', 'installed_at', 'metadata'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'installed_at' => 'datetime', 'metadata' => 'array'];
    }
}
