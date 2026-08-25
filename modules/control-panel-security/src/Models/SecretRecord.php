<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class SecretRecord extends Model
{
    use HasUuids;

    protected $table = 'control_panel_security_secrets';

    protected $fillable = ['team_id', 'name', 'purpose', 'value', 'version', 'status', 'expires_at', 'rotated_at'];

    protected $hidden = ['value'];

    protected function casts(): array
    {
        return ['value' => 'encrypted', 'expires_at' => 'datetime', 'rotated_at' => 'datetime'];
    }
}
