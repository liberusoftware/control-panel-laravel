<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Files\Models;
use Illuminate\Database\Eloquent\Model;
final class FileQuota extends Model { protected $table='control_panel_file_quotas'; protected $fillable=['id','team_id','owner_id','limit_bytes','used_bytes','files_count']; protected function casts():array{return ['limit_bytes'=>'integer','used_bytes'=>'integer','files_count'=>'integer'];} }
