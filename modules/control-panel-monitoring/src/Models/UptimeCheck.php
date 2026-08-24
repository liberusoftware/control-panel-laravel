<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Monitoring\Models;
use Illuminate\Database\Eloquent\Model;
final class UptimeCheck extends Model { protected $table='control_panel_monitoring_uptime'; protected $fillable=['id','team_id','monitor_id','endpoint','status_code','response_time_ms','healthy','checked_at','details']; protected function casts():array{return ['status_code'=>'integer','response_time_ms'=>'integer','healthy'=>'boolean','checked_at'=>'datetime','details'=>'array'];} }
