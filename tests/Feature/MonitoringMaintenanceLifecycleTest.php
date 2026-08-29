<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Monitoring\Actions\CancelMaintenanceWindow;
use Liberu\ControlPanel\Monitoring\Actions\DeleteMaintenanceWindow;
use Liberu\ControlPanel\Monitoring\Actions\RecordMonitoringResource;
use Liberu\ControlPanel\Monitoring\Models\MaintenanceWindow;
use Liberu\ControlPanel\Monitoring\MonitoringServiceProvider;
use Liberu\ControlPanel\MonitoringApi\MonitoringApiServiceProvider;
use Liberu\ControlPanel\MonitoringLivewire\Components\MonitoringFeatureInventory;
use Liberu\ControlPanel\MonitoringLivewire\MonitoringLivewireServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(MonitoringServiceProvider::class);
    app()->register(MonitoringApiServiceProvider::class);
    app()->register(MonitoringLivewireServiceProvider::class);
    $this->artisan('migrate');
});

it('cancels scheduled maintenance and rejects terminal repeats', function (): void {
    $window = app(RecordMonitoringResource::class)->execute(['team_id' => 'team-1', 'kind' => 'maintenance', 'name' => 'Upgrade', 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'scope' => 'cluster-1']);
    $cancelled = app(CancelMaintenanceWindow::class)->execute($window);
    expect($cancelled->status)->toBe('cancelled');
    expect(fn () => app(CancelMaintenanceWindow::class)->execute($cancelled))->toThrow(ValidationException::class);
    app(DeleteMaintenanceWindow::class)->execute($cancelled);
    expect(MaintenanceWindow::query()->whereKey($window->getKey())->exists())->toBeFalse();
});

it('cancels only a current-team maintenance window through API and Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $window = app(RecordMonitoringResource::class)->execute(['team_id' => $team->getKey(), 'kind' => 'maintenance', 'name' => 'Upgrade', 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'scope' => 'cluster-1']);
    $otherWindow = app(RecordMonitoringResource::class)->execute(['team_id' => $otherTeam->getKey(), 'kind' => 'maintenance', 'name' => 'Other', 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'scope' => 'cluster-2']);

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/monitoring/maintenance/'.$window->getKey().'/cancel')->assertOk()->assertJsonPath('data.attributes.status', 'cancelled');
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/monitoring/maintenance/'.$otherWindow->getKey().'/cancel')->assertNotFound();

    $livewireWindow = app(RecordMonitoringResource::class)->execute(['team_id' => $team->getKey(), 'kind' => 'maintenance', 'name' => 'Livewire', 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'scope' => 'cluster-3']);
    $this->actingAs($user);
    app(MonitoringFeatureInventory::class)->cancelMaintenance($livewireWindow->getKey(), app(CancelMaintenanceWindow::class));
    expect(MaintenanceWindow::query()->findOrFail($livewireWindow->getKey())->status)->toBe('cancelled');
    app(MonitoringFeatureInventory::class)->deleteMaintenance($livewireWindow->getKey(), app(DeleteMaintenanceWindow::class));
    expect(MaintenanceWindow::query()->whereKey($livewireWindow->getKey())->exists())->toBeFalse();
});
