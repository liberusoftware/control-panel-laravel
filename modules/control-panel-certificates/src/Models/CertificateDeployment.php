<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Models;

use Illuminate\Database\Eloquent\Model;

final class CertificateDeployment extends Model
{
    protected $table = 'control_panel_certificate_deployments';

    protected $fillable = ['id', 'team_id', 'certificate_id', 'target_type', 'target_id', 'status', 'deployed_at', 'error', 'metadata'];

    protected function casts(): array
    {
        return ['deployed_at' => 'datetime', 'metadata' => 'array'];
    }
}
