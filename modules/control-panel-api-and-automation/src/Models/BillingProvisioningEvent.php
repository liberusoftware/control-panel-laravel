<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class BillingProvisioningEvent extends Model
{
    use HasUuids;
    protected $table = 'control_panel_billing_provisioning_events';
    protected $fillable = ['team_id', 'external_id', 'event_type', 'payload', 'status', 'processed_at', 'error'];
    protected function casts(): array { return ['payload' => 'array', 'processed_at' => 'datetime']; }
}
