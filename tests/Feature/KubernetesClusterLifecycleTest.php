<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Kubernetes\Actions\ArchiveCluster;
use Liberu\ControlPanel\Kubernetes\Actions\RegisterCluster;
use Liberu\ControlPanel\Kubernetes\Actions\SuspendCluster;
use Liberu\ControlPanel\Kubernetes\KubernetesServiceProvider;
use Liberu\ControlPanel\Kubernetes\Models\Cluster;
use Liberu\ControlPanel\KubernetesApi\KubernetesApiServiceProvider;
use Liberu\ControlPanel\KubernetesLivewire\Components\ClusterInventory;
use Liberu\ControlPanel\KubernetesLivewire\KubernetesLivewireServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(KubernetesServiceProvider::class);
    app()->register(KubernetesApiServiceProvider::class);
    app()->register(KubernetesLivewireServiceProvider::class);
    $this->artisan('migrate');
});

it('suspends and archives a cluster and rejects invalid repeats', function (): void {
    $cluster = app(RegisterCluster::class)->execute(['team_id' => 'team-1', 'name' => 'Primary', 'endpoint' => 'https://k8s.example.test']);
    $suspended = app(SuspendCluster::class)->execute($cluster);
    expect($suspended->status)->toBe('suspended');
    expect(fn () => app(SuspendCluster::class)->execute($suspended))->toThrow(ValidationException::class);
    $archived = app(ArchiveCluster::class)->execute($suspended);
    expect($archived->status)->toBe('archived');
    expect(fn () => app(ArchiveCluster::class)->execute($archived))->toThrow(ValidationException::class);
});

it('exposes tenant-scoped cluster lifecycle actions through API and Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $cluster = app(RegisterCluster::class)->execute(['team_id' => $team->getKey(), 'name' => 'Primary', 'endpoint' => 'https://k8s.example.test']);
    $otherCluster = app(RegisterCluster::class)->execute(['team_id' => $otherTeam->getKey(), 'name' => 'Other', 'endpoint' => 'https://other.example.test']);

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/kubernetes/'.$cluster->getKey().'/suspend')->assertOk()->assertJsonPath('data.attributes.status', 'suspended');
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/kubernetes/'.$otherCluster->getKey().'/archive')->assertNotFound();

    $this->actingAs($user);
    $component = app(ClusterInventory::class);
    $component->archive($cluster->getKey(), app(ArchiveCluster::class));
    expect(Cluster::query()->findOrFail($cluster->getKey())->status)->toBe('archived');
});
