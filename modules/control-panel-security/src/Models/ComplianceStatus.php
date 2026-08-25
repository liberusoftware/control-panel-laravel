<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ComplianceStatus extends Model
{
    use HasUuids;

    protected $table = 'control_panel_compliance_statuses';

    protected $fillable = ['team_id', 'framework', 'control', 'status', 'score', 'evidence', 'assessed_at', 'expires_at'];

    protected function casts(): array
    {
        return ['score' => 'integer', 'evidence' => 'array', 'assessed_at' => 'datetime', 'expires_at' => 'datetime'];
    }
}
