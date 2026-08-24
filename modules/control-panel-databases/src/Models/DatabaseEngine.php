<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DatabaseEngine extends Model
{
    use HasUuids;

    protected $table = 'control_panel_database_engines';

    protected $fillable = ['team_id', 'name', 'driver', 'version', 'host', 'port', 'active', 'metadata'];

    protected function casts(): array
    {
        return ['active' => 'bool', 'metadata' => 'array'];
    }

    public function databases(): HasMany
    {
        return $this->hasMany(Database::class, 'engine_id');
    }
}
