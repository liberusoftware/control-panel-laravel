<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\Mail\Actions\CreateMailAccount;
use Liberu\ControlPanel\Mail\Actions\DeleteMailAccount;
use Liberu\ControlPanel\Mail\Actions\UpdateMailAccount;
use Liberu\ControlPanel\Mail\MailServiceProvider;
use Liberu\ControlPanel\Mail\Models\MailAccount;
use Liberu\ControlPanel\MailApi\MailApiServiceProvider;
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

it('rejects mail operations targeting another team account', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(CreateMailAccount::class)->execute(['team_id' => $otherTeam->getKey(), 'domain' => 'foreign-operation.test', 'address' => 'support']);

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/mail/operations', ['mail_account_id' => $foreign->getKey(), 'operation' => 'deliver'])->assertNotFound();
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/mail/controls', ['mail_account_id' => $foreign->getKey(), 'spam_threshold' => 5])->assertNotFound();
});
