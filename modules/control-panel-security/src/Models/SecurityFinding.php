<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class SecurityFinding extends Model
{
    use HasUuids;

    protected $table = 'control_panel_security_findings';

    protected $fillable = ['team_id', 'subject_type', 'subject_id', 'code', 'severity', 'status', 'summary', 'evidence'];

    protected function casts(): array
    {
        return ['evidence' => 'array'];
    }
}
