<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Dns\Models;
use Illuminate\Database\Eloquent\Model;
final class DnssecKey extends Model { protected $table='control_panel_dnssec_keys'; protected $fillable=['id','team_id','zone_id','key_tag','algorithm','digest_type','digest','public_key','private_key','active','rotated_at']; protected $hidden=['private_key']; protected function casts():array{return ['private_key'=>'encrypted','active'=>'boolean','rotated_at'=>'datetime'];} }
