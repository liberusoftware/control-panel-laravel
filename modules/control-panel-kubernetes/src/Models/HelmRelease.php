<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Kubernetes\Models;
use Illuminate\Database\Eloquent\Model;
final class HelmRelease extends Model { protected $table='control_panel_kubernetes_helm_releases'; protected $fillable=['id','team_id','cluster_id','namespace','name','chart','version','values','status']; protected function casts():array{return ['values'=>'array'];} }
