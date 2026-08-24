<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class HostedApplication extends Model
{
    use HasUuids;
    protected $table = 'control_panel_hosted_applications';
    protected $fillable = ['team_id', 'domain_id', 'name', 'type', 'version', 'document_root', 'status', 'config'];
    protected function casts(): array { return ['config' => 'array']; }
}
