<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Mail\Actions;
use Illuminate\Support\Str; use Illuminate\Validation\ValidationException; use Liberu\ControlPanel\Mail\Models\MailAlias;
final class CreateMailAlias { public function execute(array $a):MailAlias { $address=trim((string)($a['address']??'')); $destinations=array_values(array_filter(array_map('trim',$a['destinations']??[]))); if(!filter_var($address.'@'.($a['domain']??''),FILTER_VALIDATE_EMAIL)||$destinations===[]) throw ValidationException::withMessages(['alias'=>'A valid alias and at least one destination are required.']); return MailAlias::query()->create(['id'=>(string)Str::uuid(),'team_id'=>$a['team_id']??null,'domain'=>$a['domain'],'address'=>$address,'destinations'=>$destinations,'active'=>true]); } }
