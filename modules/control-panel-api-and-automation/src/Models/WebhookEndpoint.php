<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class WebhookEndpoint extends Model
{
    use HasUuids;

    protected $table = 'control_panel_automation_webhooks';

    protected $fillable = ['team_id', 'name', 'url', 'events', 'secret', 'status', 'retry_limit', 'last_delivered_at', 'failure_count'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return ['events' => 'array', 'secret' => 'encrypted', 'retry_limit' => 'integer', 'last_delivered_at' => 'datetime', 'failure_count' => 'integer'];
    }
}
