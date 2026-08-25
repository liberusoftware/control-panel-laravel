<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Mail\Actions;
use Illuminate\Support\Str; use Liberu\ControlPanel\Mail\Models\DeliveryDiagnostic;
final class RecordDeliveryDiagnostic { public function execute(array $a):DeliveryDiagnostic { return DeliveryDiagnostic::query()->create(['id'=>(string)Str::uuid(),'team_id'=>$a['team_id']??null,'mail_account_id'=>$a['mail_account_id']??null,'message_id'=>$a['message_id']??null,'recipient'=>$a['recipient'],'status'=>$a['status']??'pending','response'=>$a['response']??null,'checked_at'=>now()]); } }
