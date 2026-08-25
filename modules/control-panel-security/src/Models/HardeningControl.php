<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class HardeningControl extends Model
{
    use HasUuids;
    protected $table = 'control_panel_hardening_controls';
    protected $fillable = ['team_id', 'subject_type', 'subject_id', 'control', 'desired', 'observed', 'status', 'evidence', 'checked_at'];
    protected function casts(): array { return ['desired' => 'bool', 'observed' => 'bool', 'evidence' => 'array', 'checked_at' => 'datetime']; }
}
