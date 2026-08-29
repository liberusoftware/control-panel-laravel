<?php

declare(strict_types=1);
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Certificates\Actions\IssueCertificate;
use Liberu\ControlPanel\Certificates\Actions\RecordCertificateOperation;
use Liberu\ControlPanel\Certificates\Actions\RegisterAcmeAccount;
use Liberu\ControlPanel\Certificates\CertificatesServiceProvider;
use Liberu\ControlPanel\Containers\Actions\RecordContainerResource;
use Liberu\ControlPanel\Containers\Actions\RegisterWorkload;
use Liberu\ControlPanel\Containers\ContainersServiceProvider;
use Liberu\ControlPanel\Dns\Actions\CreateRecord;
use Liberu\ControlPanel\Dns\DnsServiceProvider;
use Liberu\ControlPanel\Files\Actions\RecordFileOperation;
use Liberu\ControlPanel\Files\Actions\RegisterFile;
use Liberu\ControlPanel\Files\FilesServiceProvider;
use Liberu\ControlPanel\Kubernetes\Actions\RecordKubernetesResource;
use Liberu\ControlPanel\Kubernetes\Actions\RegisterCluster;
use Liberu\ControlPanel\Kubernetes\KubernetesServiceProvider;
use Liberu\ControlPanel\Mail\Actions\RecordMailOperation;
use Liberu\ControlPanel\Mail\MailServiceProvider;
use Liberu\ControlPanel\Monitoring\Actions\RecordMonitoringEvent;
use Liberu\ControlPanel\Monitoring\Actions\RegisterMonitor;
use Liberu\ControlPanel\Monitoring\MonitoringServiceProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
    expect(fn () => app(CreateRecord::class)->execute(['team_id' => 'team-1', 'zone_id' => 'zone-1', 'type' => 'BAD', 'content' => 'x']))->toThrow(ValidationException::class);
});
it('requires tenant-owned infrastructure references', function (): void {
    $workload = app(RegisterWorkload::class)->execute(['team_id' => 'team-1', 'node_id' => (string) Str::uuid(), 'name' => 'web', 'image' => 'nginx']);
    $cluster = app(RegisterCluster::class)->execute(['team_id' => 'team-1', 'name' => 'Primary', 'endpoint' => 'https://k8s.example.test']);

    expect(fn () => app(RecordContainerResource::class)->execute(['kind' => 'image', 'name' => 'missing-team']))->toThrow(ValidationException::class);
    expect(fn () => app(RecordKubernetesResource::class)->execute(['kind' => 'namespace', 'name' => 'missing-team']))->toThrow(ValidationException::class);
    expect(fn () => app(RecordContainerResource::class)->execute(['team_id' => 'team-2', 'workload_id' => $workload->getKey(), 'kind' => 'image', 'name' => 'foreign']))->toThrow(HttpException::class);
    expect(fn () => app(RecordKubernetesResource::class)->execute(['team_id' => 'team-2', 'cluster_id' => $cluster->getKey(), 'kind' => 'namespace', 'name' => 'foreign']))->toThrow(HttpException::class);

    expect(app(RecordContainerResource::class)->execute(['team_id' => 'team-1', 'workload_id' => $workload->getKey(), 'kind' => 'image', 'name' => 'owned'])->workload_id)->toBe($workload->getKey())
        ->and(app(RecordKubernetesResource::class)->execute(['team_id' => 'team-1', 'cluster_id' => $cluster->getKey(), 'kind' => 'namespace', 'name' => 'owned'])->cluster_id)->toBe($cluster->getKey());
});
it('scopes lifecycle references to their tenant', function (): void {
    $certificate = app(IssueCertificate::class)->execute(['team_id' => 'team-1', 'domains' => ['example.test']]);
    $file = app(RegisterFile::class)->execute(['team_id' => 'team-1', 'path' => 'reports/status.json', 'disk' => 'local']);
    $monitor = app(RegisterMonitor::class)->execute(['team_id' => 'team-1', 'subject_type' => 'service', 'subject_id' => 'service-1', 'name' => 'API']);

    expect(fn () => app(RecordCertificateOperation::class)->execute(['certificate_id' => $certificate->getKey(), 'operation' => 'renew']))->toThrow(ValidationException::class);
    expect(fn () => app(RecordFileOperation::class)->execute(['file_id' => $file->getKey(), 'operation' => 'scan']))->toThrow(ValidationException::class);
    expect(fn () => app(RecordMonitoringEvent::class)->execute(['monitor_id' => $monitor->getKey(), 'kind' => 'alert']))->toThrow(ValidationException::class);
    expect(fn () => app(RecordCertificateOperation::class)->execute(['team_id' => 'team-2', 'certificate_id' => $certificate->getKey(), 'operation' => 'renew']))->toThrow(HttpException::class);
    expect(fn () => app(RecordFileOperation::class)->execute(['team_id' => 'team-2', 'file_id' => $file->getKey(), 'operation' => 'scan']))->toThrow(HttpException::class);
    expect(fn () => app(RecordMonitoringEvent::class)->execute(['team_id' => 'team-2', 'monitor_id' => $monitor->getKey(), 'kind' => 'alert']))->toThrow(HttpException::class);
});
