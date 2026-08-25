<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Monitoring\Models;
use Illuminate\Database\Eloquent\Model;
final class MaintenanceWindow extends Model { protected $table='control_panel_monitoring_maintenance'; protected $fillable=['id','team_id','name','starts_at','ends_at','scope','status','details']; protected function casts():array{return ['starts_at'=>'datetime','ends_at'=>'datetime','details'=>'array'];} }
