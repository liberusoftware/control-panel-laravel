<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ApiCredential extends Model
{
    use HasUuids;
    protected $table = 'control_panel_api_credentials';
    protected $fillable = ['team_id', 'name', 'scopes', 'secret', 'status', 'expires_at', 'last_used_at'];
    protected $hidden = ['secret'];
    protected function casts(): array { return ['scopes' => 'array', 'secret' => 'encrypted', 'expires_at' => 'datetime', 'last_used_at' => 'datetime']; }
}
