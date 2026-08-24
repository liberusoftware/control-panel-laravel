<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class AuditEntry extends Model
{
    use HasUuids;

    protected $table = 'control_panel_audit_entries';

    protected $fillable = [
        'team_id', 'actor_id', 'event', 'subject_type', 'subject_id', 'context',
    ];

    protected $hidden = ['context'];

    protected function casts(): array
    {
        return ['context' => 'array'];
    }
}
