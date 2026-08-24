<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\ControlPanel\Databases\Enums\DatabaseStatus;

final class Database extends Model
{
    use HasUuids;

    protected $table = 'control_panel_databases';

    protected $fillable = ['team_id', 'engine_id', 'account_id', 'name', 'status', 'charset', 'collation', 'metadata'];

    protected function casts(): array
    {
        return ['status' => DatabaseStatus::class, 'metadata' => 'array'];
    }

    public function engine(): BelongsTo
    {
        return $this->belongsTo(DatabaseEngine::class, 'engine_id');
    }
}
