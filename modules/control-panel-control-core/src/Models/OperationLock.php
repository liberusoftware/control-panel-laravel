<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class OperationLock extends Model
{
    use HasUuids;

    protected $table = 'control_panel_operation_locks';

    protected $fillable = ['team_id', 'node_id', 'operation_key', 'owner', 'expires_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }
}
