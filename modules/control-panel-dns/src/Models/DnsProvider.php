<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Dns\Models;
use Illuminate\Database\Eloquent\Model;
final class DnsProvider extends Model { protected $table='control_panel_dns_providers'; protected $fillable=['id','team_id','name','driver','endpoint','credentials','settings','active']; protected $hidden=['credentials']; protected function casts():array{return ['credentials'=>'encrypted:array','settings'=>'array','active'=>'boolean'];} }
