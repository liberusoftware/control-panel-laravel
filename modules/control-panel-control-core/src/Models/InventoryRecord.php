<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class InventoryRecord extends Model
{
    use HasUuids;

    protected $table = 'control_panel_inventory_records';

    protected $fillable = ['team_id', 'node_id', 'kind', 'record_key', 'value', 'observed_at'];

    protected function casts(): array
    {
        return ['value' => 'array', 'observed_at' => 'datetime'];
    }
}
