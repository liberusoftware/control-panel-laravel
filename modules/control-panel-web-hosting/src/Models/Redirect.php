<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class Redirect extends Model
{
    use HasUuids;

    protected $table = 'control_panel_redirects';

    protected $fillable = ['team_id', 'domain_id', 'source', 'destination', 'status_code', 'active', 'source_path', 'destination_url', 'redirect_type', 'match_query_string', 'is_regex', 'priority'];

    protected function casts(): array
    {
        return ['status_code' => 'integer', 'active' => 'bool', 'match_query_string' => 'bool', 'is_regex' => 'bool', 'priority' => 'integer'];
    }
}
