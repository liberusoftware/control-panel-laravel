<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\Mail\Actions\CreateMailAccount;
use Liberu\ControlPanel\Mail\Actions\CreateMailAlias;
use Liberu\ControlPanel\Mail\Actions\CreateMailRoute;
use Liberu\ControlPanel\Mail\Actions\DeleteMailAccount;
use Liberu\ControlPanel\Mail\Actions\DeleteMailAlias;
use Liberu\ControlPanel\Mail\Actions\DeleteMailRoute;
use Liberu\ControlPanel\Mail\Actions\UpdateMailAccount;
use Liberu\ControlPanel\Mail\Actions\UpdateMailAlias;
use Liberu\ControlPanel\Mail\Actions\UpdateMailRoute;
use Liberu\ControlPanel\Mail\MailServiceProvider;
use Liberu\ControlPanel\Mail\Models\MailAccount;
use Liberu\ControlPanel\Mail\Models\MailAlias;
use Liberu\ControlPanel\Mail\Models\MailRoute;
use Liberu\ControlPanel\MailApi\MailApiServiceProvider;
use Liberu\ControlPanel\MailLivewire\Components\MailFeatureInventory;
use Liberu\ControlPanel\MailLivewire\Components\MailInventory;
use Liberu\ControlPanel\MailLivewire\MailLivewireServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(MailServiceProvider::class);
    app()->register(MailApiServiceProvider::class);
    app()->register(MailLivewireServiceProvider::class);
    $this->artisan('migrate');
});

it('updates only a current-team mailbox through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(CreateMailAccount::class)->execute(['team_id' => $otherTeam->getKey(), 'domain' => 'other.test', 'address' => 'support']);
    $owned = app(CreateMailAccount::class)->execute(['team_id' => $team->getKey(), 'domain' => 'owned.test', 'address' => 'support']);

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/mail/'.$foreign->getKey(), ['quota_bytes' => 10])->assertNotFound();
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/mail/'.$owned->getKey(), ['quota_bytes' => 10])->assertOk()->assertJsonPath('data.attributes.quota_bytes', 10);
});

it('updates only a current-team mailbox from Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(CreateMailAccount::class)->execute(['team_id' => $otherTeam->getKey(), 'domain' => 'other.test', 'address' => 'support']);
    $owned = app(CreateMailAccount::class)->execute(['team_id' => $team->getKey(), 'domain' => 'owned.test', 'address' => 'support']);
    $inventory = app(MailInventory::class);
    $this->actingAs($user);

    expect(fn () => $inventory->update($foreign->getKey(), ['domain' => 'other.test', 'address' => 'support', 'quota_bytes' => 5], app(UpdateMailAccount::class)))->toThrow(ModelNotFoundException::class);
    $inventory->update($owned->getKey(), ['domain' => 'owned.test', 'address' => 'helpdesk@owned.test', 'quota_bytes' => 5], app(UpdateMailAccount::class));

    expect(MailAccount::query()->findOrFail($owned->getKey())->address)->toBe('helpdesk@owned.test');
});

it('deletes only a current-team mailbox through API and Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(CreateMailAccount::class)->execute(['team_id' => $otherTeam->getKey(), 'domain' => 'other-delete.test', 'address' => 'support']);
    $owned = app(CreateMailAccount::class)->execute(['team_id' => $team->getKey(), 'domain' => 'owned-delete.test', 'address' => 'support']);

    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/mail/'.$foreign->getKey())->assertNotFound();
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/mail/'.$owned->getKey())->assertNoContent();

    $livewireAccount = app(CreateMailAccount::class)->execute(['team_id' => $team->getKey(), 'domain' => 'owned-livewire.test', 'address' => 'support']);
    $inventory = app(MailInventory::class);
    $this->actingAs($user);
    $inventory->delete($livewireAccount->getKey(), app(DeleteMailAccount::class));

    expect(MailAccount::query()->whereKey($owned->getKey())->exists())->toBeFalse()
        ->and(MailAccount::query()->whereKey($livewireAccount->getKey())->exists())->toBeFalse();
});

it('updates and deletes only a current-team alias through API and Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(CreateMailAlias::class)->execute([
        'team_id' => $otherTeam->getKey(),
        'domain' => 'other-alias.test',
        'address' => 'support',
        'destinations' => ['other@example.test'],
    ]);
    $owned = app(CreateMailAlias::class)->execute([
        'team_id' => $team->getKey(),
        'domain' => 'owned-alias.test',
        'address' => 'support',
        'destinations' => ['ops@example.test'],
    ]);

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/mail/aliases/'.$foreign->getKey(), [
        'domain' => 'blocked.test',
        'address' => 'blocked',
        'destinations' => ['blocked@example.test'],
    ])->assertNotFound();

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/mail/aliases/'.$owned->getKey(), [
        'domain' => 'updated-alias.test',
        'address' => 'helpdesk',
        'destinations' => ['team@example.test'],
        'active' => false,
    ])->assertOk()->assertJsonPath('data.attributes.domain', 'updated-alias.test');

    $inventory = app(MailFeatureInventory::class);
    $this->actingAs($user);

    expect(fn () => $inventory->updateAlias($foreign->getKey(), [
        'domain' => 'blocked.test',
        'address' => 'blocked',
        'destinations' => ['blocked@example.test'],
    ], app(UpdateMailAlias::class)))->toThrow(ModelNotFoundException::class);

    $livewireAlias = app(CreateMailAlias::class)->execute([
        'team_id' => $team->getKey(),
        'domain' => 'livewire-alias.test',
        'address' => 'support',
        'destinations' => ['ops@example.test'],
    ]);
    $inventory->deleteAlias($livewireAlias->getKey(), app(DeleteMailAlias::class));

    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/mail/aliases/'.$foreign->getKey())->assertNotFound();
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/mail/aliases/'.$owned->getKey())->assertNoContent();

    expect(MailAlias::query()->whereKey($owned->getKey())->exists())->toBeFalse()
        ->and(MailAlias::query()->whereKey($livewireAlias->getKey())->exists())->toBeFalse();
});

it('rejects mail operations targeting another team account', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(CreateMailAccount::class)->execute(['team_id' => $otherTeam->getKey(), 'domain' => 'foreign-operation.test', 'address' => 'support']);

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/mail/operations', ['mail_account_id' => $foreign->getKey(), 'operation' => 'deliver'])->assertNotFound();
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/mail/controls', ['mail_account_id' => $foreign->getKey(), 'spam_threshold' => 5])->assertNotFound();
});

it('updates and deletes only a current-team route through API and Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(CreateMailRoute::class)->execute(['team_id' => $otherTeam->getKey(), 'domain' => 'other-route.test', 'source_pattern' => 'support', 'destination' => 'other@example.test']);
    $owned = app(CreateMailRoute::class)->execute(['team_id' => $team->getKey(), 'domain' => 'owned-route.test', 'source_pattern' => 'support', 'destination' => 'ops@example.test']);

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/mail/routes/'.$foreign->getKey(), ['destination' => 'blocked@example.test'])->assertNotFound();
    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/mail/routes/'.$owned->getKey(), ['source_pattern' => 'helpdesk', 'destination' => 'team@example.test', 'priority' => 10])->assertOk()->assertJsonPath('data.attributes.source_pattern', 'helpdesk');

    $inventory = app(MailFeatureInventory::class);
    $this->actingAs($user);
    expect(fn () => $inventory->updateRoute($foreign->getKey(), ['domain' => 'other-route.test', 'source_pattern' => 'support', 'destination' => 'other@example.test', 'priority' => 100], app(UpdateMailRoute::class)))->toThrow(ModelNotFoundException::class);

    $livewireRoute = app(CreateMailRoute::class)->execute(['team_id' => $team->getKey(), 'domain' => 'livewire-route.test', 'source_pattern' => 'support', 'destination' => 'ops@example.test']);
    $inventory->deleteRoute($livewireRoute->getKey(), app(DeleteMailRoute::class));

    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/mail/routes/'.$foreign->getKey())->assertNotFound();
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/mail/routes/'.$owned->getKey())->assertNoContent();

    expect(MailRoute::query()->whereKey($owned->getKey())->exists())->toBeFalse()
        ->and(MailRoute::query()->whereKey($livewireRoute->getKey())->exists())->toBeFalse();
});
