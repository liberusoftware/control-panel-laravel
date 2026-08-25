<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DatabaseUser extends Model
{
    use HasUuids;

    protected $table = 'control_panel_database_users';

    protected $fillable = ['team_id', 'database_id', 'username', 'host', 'password', 'active'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'encrypted', 'active' => 'bool'];
    }

    public function database(): BelongsTo
    {
        return $this->belongsTo(Database::class);
    }
}
