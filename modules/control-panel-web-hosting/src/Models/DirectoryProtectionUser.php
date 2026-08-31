<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DirectoryProtectionUser extends Model
{
    use HasUuids;

    protected $table = 'control_panel_directory_protection_users';

    protected $fillable = ['team_id', 'directory_protection_id', 'username', 'password'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function directoryProtection(): BelongsTo
    {
        return $this->belongsTo(DirectoryProtection::class);
    }
}
