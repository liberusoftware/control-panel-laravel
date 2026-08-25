<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MfaRbacPolicy extends Model
{
    use HasUuids;
    protected $table = 'control_panel_mfa_rbac_policies';
    protected $fillable = ['team_id', 'subject_type', 'subject_id', 'mfa_required', 'roles', 'permissions', 'status', 'metadata'];
    protected function casts(): array { return ['mfa_required' => 'bool', 'roles' => 'array', 'permissions' => 'array', 'metadata' => 'array']; }
}
