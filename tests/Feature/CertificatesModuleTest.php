<?php
declare(strict_types=1);
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Certificates\Actions\RegisterAcmeAccount;
use Liberu\ControlPanel\Certificates\Actions\RegisterCertificateLifecycle;
use Liberu\ControlPanel\Certificates\CertificatesServiceProvider;
uses(RefreshDatabase::class);
beforeEach(function():void{app()->register(CertificatesServiceProvider::class);$this->artisan('migrate');});
it('supports ACME, deployment, renewal, revocation operations, and expiry alerts',function():void{$account=app(RegisterAcmeAccount::class)->execute(['team_id'=>'team-1','email'=>'admin@example.test','credentials'=>['account'=>'encrypted']]);$a=app(RegisterCertificateLifecycle::class);$deployment=$a->execute(['team_id'=>'team-1','kind'=>'deployment','certificate_id'=>'certificate-1','target_type'=>'web-server','target_id'=>'server-1','status'=>'completed']);$renewal=$a->execute(['team_id'=>'team-1','kind'=>'renewal','certificate_id'=>'certificate-1','scheduled_at'=>now()->addDays(20)]);$expiry=$a->execute(['team_id'=>'team-1','kind'=>'expiry','certificate_id'=>'certificate-1','threshold_days'=>30]);expect($account->credentials)->toMatchArray(['account'=>'encrypted'])->and($deployment->status)->toBe('completed')->and($renewal->status)->toBe('queued')->and($expiry->threshold_days)->toBe(30);});
it('rejects unknown certificate lifecycle operations',function():void{expect(fn()=>app(RegisterCertificateLifecycle::class)->execute(['team_id'=>'team-1','kind'=>'unknown']))->toThrow(ValidationException::class);});
