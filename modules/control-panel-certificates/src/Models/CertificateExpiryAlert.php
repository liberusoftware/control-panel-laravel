<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class CertificateExpiryAlert extends Model
{
    use HasUuids;

    protected $table = 'control_panel_certificate_expiry_alerts';

    protected $fillable = ['id', 'team_id', 'certificate_id', 'threshold_days', 'status', 'notified_at', 'metadata'];

    protected function casts(): array
    {
        return ['threshold_days' => 'integer', 'notified_at' => 'datetime', 'metadata' => 'array'];
    }
}
