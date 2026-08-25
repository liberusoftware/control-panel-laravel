<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Monitoring\Models;
use Illuminate\Database\Eloquent\Model;
final class CapacitySnapshot extends Model { protected $table='control_panel_monitoring_capacity'; protected $fillable=['id','team_id','resource','used','available','unit','captured_at','details']; protected function casts():array{return ['used'=>'float','available'=>'float','captured_at'=>'datetime','details'=>'array'];} }
