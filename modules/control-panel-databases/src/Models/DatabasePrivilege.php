<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DatabasePrivilege extends Model
{
    use HasUuids;

    protected $table = 'control_panel_database_privileges';

    protected $fillable = ['team_id', 'database_id', 'database_user_id', 'privilege', 'object_name'];

    public function database(): BelongsTo
    {
        return $this->belongsTo(Database::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(DatabaseUser::class, 'database_user_id');
    }
}
