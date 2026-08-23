<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\ControlPanel\Accounts\Enums\AccountStatus;
use Liberu\ControlPanel\Accounts\Enums\AccountType;

final class Account extends Model
{
    use HasUuids;

    protected $table = 'control_panel_accounts';

    protected $fillable = [
        'team_id',
        'parent_id',
        'owner_id',
        'type',
        'status',
        'name',
        'brand',
        'quota_overrides',
        'suspended_reason',
        'suspended_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'status' => AccountStatus::class,
            'brand' => 'array',
            'quota_overrides' => 'array',
            'suspended_at' => 'datetime',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function isOperational(): bool
    {
        return $this->status === AccountStatus::Active;
    }
}
