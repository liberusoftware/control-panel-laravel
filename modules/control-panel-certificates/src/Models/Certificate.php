<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Certificates\Enums\CertificateStatus;

final class Certificate extends Model
{
    use HasUuids;

    protected $table = 'control_panel_certificates';

    protected $fillable = ['team_id', 'domains', 'status', 'issuer', 'certificate_pem', 'private_key', 'issued_at', 'expires_at', 'metadata'];

    protected $hidden = ['private_key'];

    protected function casts(): array
    {
        return ['domains' => 'array', 'status' => CertificateStatus::class, 'private_key' => 'encrypted', 'issued_at' => 'datetime', 'expires_at' => 'datetime', 'metadata' => 'array'];
    }
}
