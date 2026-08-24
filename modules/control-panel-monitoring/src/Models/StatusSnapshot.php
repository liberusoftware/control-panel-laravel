<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Monitoring\Models;
use Illuminate\Database\Eloquent\Model;
final class StatusSnapshot extends Model { protected $table='control_panel_monitoring_status'; protected $fillable=['id','team_id','component','status','message','checked_at','metadata']; protected function casts():array{return ['checked_at'=>'datetime','metadata'=>'array'];} }
