<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\ControlCore\Actions;
use Illuminate\Support\Str; use Illuminate\Validation\ValidationException; use Liberu\ControlPanel\ControlCore\Enums\CredentialStatus; use Liberu\ControlPanel\ControlCore\Models\NodeCredential;
final class RegisterNodeCredential { public function execute(array $a):NodeCredential { $name=trim((string)($a['name']??'')); if($name===''||empty($a['node_id'])||empty($a['secret'])&&empty($a['public_key']))throw ValidationException::withMessages(['credential'=>'A node, name, and secret or public key are required.']); return NodeCredential::query()->create(['id'=>(string)Str::uuid(),'team_id'=>$a['team_id']??null,'node_id'=>$a['node_id'],'name'=>$name,'type'=>$a['type']??'ssh','username'=>$a['username']??null,'secret'=>$a['secret']??null,'public_key'=>$a['public_key']??null,'status'=>CredentialStatus::Active,'expires_at'=>$a['expires_at']??null,'metadata'=>$a['metadata']??[]]); } }
