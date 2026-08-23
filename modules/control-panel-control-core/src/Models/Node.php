<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Liberu\ControlPanel\ControlCore\Enums\NodeStatus;

final class Node extends Model
{
    use HasUuids;

    protected $table = 'control_panel_nodes';

    protected $fillable = [
        'team_id',
        'name',
        'hostname',
        'platform',
        'status',
        'credentials',
        'desired_state',
        'observed_state',
        'last_seen_at',
    ];

    protected $hidden = [
        'credentials',
    ];

    protected function casts(): array
    {
        return [
            'status' => NodeStatus::class,
            'desired_state' => 'array',
            'observed_state' => 'array',
            'credentials' => 'encrypted:array',
            'last_seen_at' => 'datetime',
        ];
    }

    public function capabilities(): HasMany
    {
        return $this->hasMany(NodeCapability::class);
    }

    public function isOperational(): bool
    {
        return $this->status === NodeStatus::Active;
    }
}
