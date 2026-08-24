<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class VirtualHost extends Model
{
    use HasUuids;

    protected $table = 'control_panel_virtual_hosts';

    protected $fillable = ['domain_id', 'node_id', 'server', 'runtime', 'document_root', 'desired_state', 'active'];

    protected function casts(): array
    {
        return ['desired_state' => 'array', 'active' => 'bool'];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
