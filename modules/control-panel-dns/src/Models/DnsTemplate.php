<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Dns\Models;
use Illuminate\Database\Eloquent\Model;
final class DnsTemplate extends Model { protected $table='control_panel_dns_templates'; protected $fillable=['id','team_id','name','records','active']; protected function casts(): array { return ['records'=>'array','active'=>'boolean']; } }
