<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Record extends Model
{
    use HasUuids;

    protected $table = 'control_panel_dns_records';

    protected $fillable = ['zone_id', 'name', 'type', 'content', 'ttl', 'priority', 'metadata'];

    protected function casts(): array
    {
        return ['ttl' => 'integer', 'priority' => 'integer', 'metadata' => 'array'];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
