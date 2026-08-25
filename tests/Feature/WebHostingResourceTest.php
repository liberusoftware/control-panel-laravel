<?php
declare(strict_types=1);
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Actions\RegisterHostingResource;
use Liberu\ControlPanel\WebHosting\Actions\CreateDomain;
use Liberu\ControlPanel\WebHosting\WebHostingServiceProvider;
uses(RefreshDatabase::class);
beforeEach(function():void{app()->register(WebHostingServiceProvider::class);$this->artisan('migrate');});
it('supports runtimes, web servers, logs, and hosted applications',function():void{$a=app(RegisterHostingResource::class);$domain=app(CreateDomain::class)->execute(['team_id'=>'team-1','hostname'=>'example.test']);$runtime=$a->execute(['team_id'=>'team-1','kind'=>'runtime','runtime'=>'php','version'=>'8.5']);$server=$a->execute(['team_id'=>'team-1','kind'=>'server','node_id'=>'node-1','server'=>'nginx','version'=>'1.27']);$log=$a->execute(['team_id'=>'team-1','kind'=>'log','domain_id'=>$domain->id,'level'=>'info','message'=>'deployed']);$application=$a->execute(['team_id'=>'team-1','kind'=>'application','domain_id'=>$domain->id,'name'=>'app','type'=>'laravel','document_root'=>'/var/www/app']);expect($runtime->version)->toBe('8.5')->and($server->status)->toBe('active')->and($log->message)->toBe('deployed')->and($application->status)->toBe('pending');});
it('rejects unsupported hosting resources',function():void{expect(fn()=>app(RegisterHostingResource::class)->execute(['team_id'=>'team-1','kind'=>'unknown']))->toThrow(ValidationException::class);});
