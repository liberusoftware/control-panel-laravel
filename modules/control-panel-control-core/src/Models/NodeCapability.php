<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NodeCapability extends Model
{
    use HasUuids;

    protected $table = 'control_panel_node_capabilities';

    protected $fillable = ['node_id', 'name', 'version', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }
}
