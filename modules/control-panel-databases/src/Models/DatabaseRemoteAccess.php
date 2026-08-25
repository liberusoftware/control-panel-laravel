<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class DatabaseRemoteAccess extends Model
{
    use HasUuids;

    protected $table = 'control_panel_database_remote_access';

    protected $fillable = ['team_id', 'database_id', 'source_cidr', 'port', 'tls_required', 'active', 'expires_at', 'metadata'];

    protected function casts(): array
    {
        return ['port' => 'integer', 'tls_required' => 'bool', 'active' => 'bool', 'expires_at' => 'datetime', 'metadata' => 'array'];
    }
}
