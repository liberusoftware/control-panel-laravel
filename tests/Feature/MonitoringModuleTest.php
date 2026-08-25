<?php

declare(strict_types=1);
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Monitoring\Actions\RecordMonitoringResource;
use Liberu\ControlPanel\Monitoring\Actions\ResolveMonitoringEvent;
use Liberu\ControlPanel\Monitoring\Models\MonitoringEvent;
use Liberu\ControlPanel\Monitoring\MonitoringServiceProvider;

uses(RefreshDatabase::class);
beforeEach(function (): void {
    app()->register(MonitoringServiceProvider::class);
    $this->artisan('migrate');
});
it('records metrics, logs, uptime, capacity, alerts, incidents, maintenance, and status', function (): void {
    $a = app(RecordMonitoringResource::class);
    $metric = $a->execute(['team_id' => 'team-1', 'kind' => 'metric', 'name' => 'cpu', 'value' => 42, 'unit' => 'percent']);
    $log = $a->execute(['team_id' => 'team-1', 'kind' => 'log', 'source' => 'fpm', 'level' => 'error', 'message' => 'failed']);
    $uptime = $a->execute(['team_id' => 'team-1', 'kind' => 'uptime', 'endpoint' => 'https://example.test', 'healthy' => true]);
    $capacity = $a->execute(['team_id' => 'team-1', 'kind' => 'capacity', 'resource' => 'disk', 'used' => 10, 'available' => 90, 'unit' => 'gb']);
    $alert = $a->execute(['team_id' => 'team-1', 'kind' => 'alert', 'name' => 'CPU', 'condition' => 'above', 'threshold' => 80, 'channels' => ['email']]);
    $incident = $a->execute(['team_id' => 'team-1', 'kind' => 'incident', 'title' => 'Outage', 'severity' => 'high']);
    $maintenance = $a->execute(['team_id' => 'team-1', 'kind' => 'maintenance', 'name' => 'Upgrade', 'starts_at' => now(), 'ends_at' => now()->addHour(), 'scope' => 'node']);
    $status = $a->execute(['team_id' => 'team-1', 'kind' => 'status', 'component' => 'api', 'status' => 'operational']);
    expect($metric->name)->toBe('cpu')->and($log->level)->toBe('error')->and($uptime->healthy)->toBeTrue()->and($capacity->unit)->toBe('gb')->and($alert->threshold)->toBe(80.0)->and($incident->severity)->toBe('high')->and($maintenance->status)->toBe('scheduled')->and($status->status)->toBe('operational');
});
it('rejects unknown monitoring resources', function (): void {
    expect(fn () => app(RecordMonitoringResource::class)->execute(['team_id' => 'team-1', 'kind' => 'unknown']))->toThrow(ValidationException::class);
});

it('resolves only open incident events', function (): void {
    $event = MonitoringEvent::query()->create(['id' => (string) Str::uuid(), 'team_id' => 'team-1', 'kind' => 'incident', 'status' => 'open', 'payload' => []]);

    expect(app(ResolveMonitoringEvent::class)->execute($event)->status)->toBe('resolved')
        ->and($event->fresh()->ends_at)->not->toBeNull();
    expect(fn () => app(ResolveMonitoringEvent::class)->execute($event->fresh()))->toThrow(ValidationException::class);
});
