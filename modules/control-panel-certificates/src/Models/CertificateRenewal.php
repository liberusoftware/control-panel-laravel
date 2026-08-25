<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Models;

use Illuminate\Database\Eloquent\Model;

final class CertificateRenewal extends Model
{
    protected $table = 'control_panel_certificate_renewals';

    protected $fillable = ['id', 'team_id', 'certificate_id', 'scheduled_at', 'started_at', 'completed_at', 'status', 'attempts', 'error'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'attempts' => 'integer'];
    }
}
