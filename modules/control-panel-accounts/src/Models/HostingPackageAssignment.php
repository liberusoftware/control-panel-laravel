<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HostingPackageAssignment extends Model
{
    use HasUuids;

    protected $table = 'control_panel_hosting_package_assignments';

    protected $fillable = ['team_id', 'account_id', 'hosting_package_id', 'start_date', 'end_date', 'active'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'active' => 'bool'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function hostingPackage(): BelongsTo
    {
        return $this->belongsTo(HostingPackage::class);
    }
}
