<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AccountDelegation extends Model
{
    use HasUuids;

    protected $table = 'control_panel_account_delegations';

    protected $fillable = ['team_id', 'account_id', 'delegate_id', 'permissions', 'expires_at', 'active'];

    protected function casts(): array
    {
        return ['permissions' => 'array', 'expires_at' => 'datetime', 'active' => 'bool'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
