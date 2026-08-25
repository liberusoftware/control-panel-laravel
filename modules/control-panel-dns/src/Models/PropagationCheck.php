<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Dns\Models;
use Illuminate\Database\Eloquent\Model;
final class PropagationCheck extends Model { protected $table='control_panel_dns_propagation_checks'; protected $fillable=['id','team_id','zone_id','record_id','status','nameservers','results','checked_at']; protected function casts():array{return ['nameservers'=>'array','results'=>'array','checked_at'=>'datetime'];} }
