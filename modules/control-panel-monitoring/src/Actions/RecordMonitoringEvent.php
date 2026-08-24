<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Monitoring\Actions;
use Illuminate\Support\Str; use Illuminate\Validation\ValidationException; use Liberu\ControlPanel\Monitoring\Models\MonitoringEvent;
final class RecordMonitoringEvent { public function execute(array $a):MonitoringEvent { $kind=(string)($a['kind']??''); if(!in_array($kind,['metric','log','uptime','capacity','alert','incident','maintenance','status'],true))throw ValidationException::withMessages(['kind'=>'Unsupported monitoring event.']); return MonitoringEvent::query()->create(['id'=>(string)Str::uuid(),'team_id'=>$a['team_id']??null,'monitor_id'=>$a['monitor_id']??null,'kind'=>$kind,'status'=>$a['status']??'open','payload'=>$a['payload']??[],'starts_at'=>$a['starts_at']??now(),'ends_at'=>$a['ends_at']??null]); } }
