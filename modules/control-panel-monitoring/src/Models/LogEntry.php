<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Monitoring\Models;
use Illuminate\Database\Eloquent\Model;
final class LogEntry extends Model { protected $table='control_panel_monitoring_logs'; protected $fillable=['id','team_id','source','level','message','context','logged_at']; protected function casts():array{return ['context'=>'array','logged_at'=>'datetime'];} }
