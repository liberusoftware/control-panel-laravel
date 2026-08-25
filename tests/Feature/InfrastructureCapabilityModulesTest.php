<?php

declare(strict_types=1);
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Certificates\Actions\RegisterAcmeAccount;
use Liberu\ControlPanel\Certificates\CertificatesServiceProvider;
use Liberu\ControlPanel\Containers\Actions\RecordContainerResource;
use Liberu\ControlPanel\Containers\ContainersServiceProvider;
use Liberu\ControlPanel\Dns\Actions\CreateRecord;
use Liberu\ControlPanel\Dns\DnsServiceProvider;
use Liberu\ControlPanel\Files\Actions\RecordFileOperation;
use Liberu\ControlPanel\Files\FilesServiceProvider;
use Liberu\ControlPanel\Kubernetes\Actions\RecordKubernetesResource;
use Liberu\ControlPanel\Kubernetes\KubernetesServiceProvider;
use Liberu\ControlPanel\Mail\Actions\RecordMailOperation;
use Liberu\ControlPanel\Mail\MailServiceProvider;
use Liberu\ControlPanel\Monitoring\Actions\RecordMonitoringEvent;
use Liberu\ControlPanel\Monitoring\MonitoringServiceProvider;

uses(RefreshDatabase::class);
beforeEach(function (): void {
    foreach ([FilesServiceProvider::class, CertificatesServiceProvider::class, DnsServiceProvider::class, MailServiceProvider::class, MonitoringServiceProvider::class, ContainersServiceProvider::class, KubernetesServiceProvider::class] as $provider) {
        app()->register($provider);
    } $this->artisan('migrate');
});
it('validates and records cross-cutting infrastructure lifecycle operations', function (): void {
    $certificate = app(RegisterAcmeAccount::class)->execute(['team_id' => 'team-1', 'email' => 'ops@example.test']);
    $mail = app(RecordMailOperation::class)->execute(['team_id' => 'team-1', 'operation' => 'deliver']);
    $monitor = app(RecordMonitoringEvent::class)->execute(['team_id' => 'team-1', 'kind' => 'alert']);
    $container = app(RecordContainerResource::class)->execute(['team_id' => 'team-1', 'kind' => 'image', 'name' => 'app']);
    $kubernetes = app(RecordKubernetesResource::class)->execute(['team_id' => 'team-1', 'kind' => 'namespace', 'name' => 'production']);
    expect($certificate->email)->toBe('ops@example.test')->and($mail->operation)->toBe('deliver')->and($monitor->kind)->toBe('alert')->and($container->kind)->toBe('image')->and($kubernetes->kind)->toBe('namespace');
});
it('rejects unsupported infrastructure operations', function (): void {
    expect(fn () => app(RecordFileOperation::class)->execute(['team_id' => 'team-1', 'operation' => 'chmod']))->toThrow(ValidationException::class);
    expect(fn () => app(RecordMonitoringEvent::class)->execute(['team_id' => 'team-1', 'kind' => 'unknown']))->toThrow(ValidationException::class);
    expect(fn () => app(RecordContainerResource::class)->execute(['team_id' => 'team-1', 'kind' => 'unknown', 'name' => 'x']))->toThrow(ValidationException::class);
    expect(fn () => app(RecordKubernetesResource::class)->execute(['team_id' => 'team-1', 'kind' => 'unknown', 'name' => 'x']))->toThrow(ValidationException::class);
    expect(fn () => app(CreateRecord::class)->execute(['zone_id' => 'zone-1', 'type' => 'BAD', 'content' => 'x']))->toThrow(ValidationException::class);
});
