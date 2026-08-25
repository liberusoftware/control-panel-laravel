<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\ControlCore\Enums\CredentialStatus;

final class NodeCredential extends Model
{
    use HasUuids;

    protected $table = 'control_panel_node_credentials';

    protected $fillable = ['id', 'team_id', 'node_id', 'name', 'type', 'username', 'secret', 'public_key', 'status', 'expires_at', 'last_used_at', 'metadata'];

    protected $hidden = ['secret', 'public_key'];

    protected function casts(): array
    {
        return ['secret' => 'encrypted', 'public_key' => 'encrypted', 'status' => CredentialStatus::class, 'expires_at' => 'datetime', 'last_used_at' => 'datetime', 'metadata' => 'array'];
    }
}
