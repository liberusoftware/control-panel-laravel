<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Certificates\Actions;
use Illuminate\Database\Eloquent\Model; use Illuminate\Support\Str; use Illuminate\Validation\ValidationException;
final class RegisterCertificateLifecycle { public function execute(array $a):Model { $kind=(string)($a['kind']??''); $map=['deployment'=>\Liberu\ControlPanel\Certificates\Models\CertificateDeployment::class,'renewal'=>\Liberu\ControlPanel\Certificates\Models\CertificateRenewal::class,'expiry'=>\Liberu\ControlPanel\Certificates\Models\CertificateExpiryAlert::class]; if(!isset($map[$kind]))throw ValidationException::withMessages(['kind'=>'Unsupported certificate lifecycle operation.']); $a['id']=$a['id']??(string)Str::uuid();$a['team_id']=$a['team_id']??null; $a['status']=$a['status']??($kind==='expiry'?'pending':'queued'); unset($a['kind']);return $map[$kind]::query()->create($a); } }
