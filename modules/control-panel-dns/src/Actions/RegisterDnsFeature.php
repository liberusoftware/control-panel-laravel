<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Dns\Actions;
use Illuminate\Database\Eloquent\Model; use Illuminate\Support\Str; use Illuminate\Validation\ValidationException;
final class RegisterDnsFeature { public function execute(array $a):Model { $kind=(string)($a['kind']??''); $map=['template'=>\Liberu\ControlPanel\Dns\Models\DnsTemplate::class,'dnssec'=>\Liberu\ControlPanel\Dns\Models\DnssecKey::class,'provider'=>\Liberu\ControlPanel\Dns\Models\DnsProvider::class,'validation'=>\Liberu\ControlPanel\Dns\Models\DnsValidation::class,'propagation'=>\Liberu\ControlPanel\Dns\Models\PropagationCheck::class]; if(!isset($map[$kind]))throw ValidationException::withMessages(['kind'=>'Unsupported DNS feature.']); $a['id']=$a['id']??(string)Str::uuid();$a['team_id']=$a['team_id']??null;unset($a['kind']);return $map[$kind]::query()->create($a); } }
