<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Files\Actions\CreateSftpAccount;
use Liberu\ControlPanel\Files\Actions\DeleteSftpAccount;
use Liberu\ControlPanel\Files\Actions\RegenerateSftpKeyPair;
use Liberu\ControlPanel\Files\Actions\RegisterFile;
use Liberu\ControlPanel\Files\Actions\SetFileQuota;
use Liberu\ControlPanel\Files\Enums\FileStatus;
use Liberu\ControlPanel\Files\FilesServiceProvider;
use Liberu\ControlPanel\Files\Models\FileQuota;
use Liberu\ControlPanel\FilesApi\FilesApiServiceProvider;
use Liberu\ControlPanel\FilesLivewire\Components\QuotaInventory;
use Liberu\ControlPanel\FilesLivewire\Components\SftpInventory;
use Liberu\ControlPanel\FilesLivewire\FilesLivewireServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(FilesServiceProvider::class);
    app()->register(FilesApiServiceProvider::class);
    app()->register(FilesLivewireServiceProvider::class);
    $this->artisan('migrate');
});

it('sets a tenant quota and rejects usage above a finite limit', function (): void {
    $quota = app(SetFileQuota::class)->execute(['team_id' => 'team-1', 'owner_id' => 'owner-1', 'limit_bytes' => 1000, 'used_bytes' => 250, 'files_count' => 4]);
    expect($quota->used_bytes)->toBe(250);

    expect(fn () => app(SetFileQuota::class)->execute(['team_id' => 'team-1', 'limit_bytes' => 100, 'used_bytes' => 101]))
        ->toThrow(ValidationException::class);
});

it('upserts a quota for the same tenant and owner', function (): void {
    app(SetFileQuota::class)->execute(['team_id' => 'team-1', 'owner_id' => 'owner-1', 'limit_bytes' => 1000]);
    app(SetFileQuota::class)->execute(['team_id' => 'team-1', 'owner_id' => 'owner-1', 'limit_bytes' => 2000]);

    expect(FileQuota::query()->where('team_id', 'team-1')->where('owner_id', 'owner-1')->count())->toBe(1)
        ->and(FileQuota::query()->first()->limit_bytes)->toBe(2000);
});

it('exposes quotas through the tenant-scoped API and Livewire component', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/files/quotas', ['owner_id' => 'owner-1', 'limit_bytes' => 5000])
        ->assertCreated()
        ->assertJsonPath('data.attributes.limit_bytes', 5000);

    $this->actingAs($user);
    $component = app(QuotaInventory::class);
    $component->ownerId = 'owner-2';
    $component->limitBytes = 7000;
    $component->save(app(SetFileQuota::class));

    expect(FileQuota::query()->where('owner_id', 'owner-2')->where('team_id', $team->getKey())->exists())->toBeTrue();
});

it('deletes only a current-team file through the API', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(RegisterFile::class)->execute(['team_id' => $otherTeam->getKey(), 'path' => 'foreign.txt', 'disk' => 'local']);
    $owned = app(RegisterFile::class)->execute(['team_id' => $team->getKey(), 'path' => 'owned.txt', 'disk' => 'local']);

    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/files/'.$foreign->getKey())->assertNotFound();
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/files/'.$owned->getKey())->assertOk()->assertJsonPath('data.attributes.status', 'deleted');

    expect($owned->refresh()->status)->toBe(FileStatus::Deleted);
});

it('deletes only a current-team SFTP account through API and Livewire', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(CreateSftpAccount::class)->execute(['team_id' => $otherTeam->getKey(), 'username' => 'foreign', 'password' => 'long-enough-secret']);
    $owned = app(CreateSftpAccount::class)->execute(['team_id' => $team->getKey(), 'username' => 'owned', 'password' => 'long-enough-secret']);

    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/files/sftp-accounts/'.$foreign->getKey())->assertNotFound();
    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/files/sftp-accounts/'.$owned->getKey())->assertNoContent();

    $livewireAccount = app(CreateSftpAccount::class)->execute(['team_id' => $team->getKey(), 'username' => 'livewire', 'password' => 'long-enough-secret']);
    $this->actingAs($user);
    app(SftpInventory::class)->delete($livewireAccount->getKey(), app(DeleteSftpAccount::class));

    expect($owned->fresh())->toBeNull()->and($livewireAccount->fresh())->toBeNull();
});

it('regenerates SFTP keys and returns private material only through the explicit API flow', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $foreign = app(CreateSftpAccount::class)->execute(['team_id' => $otherTeam->getKey(), 'username' => 'foreign-key', 'password' => 'long-enough-secret']);
    $account = app(CreateSftpAccount::class)->execute(['team_id' => $team->getKey(), 'username' => 'key-owner', 'password' => 'long-enough-secret']);

    $keys = app(RegenerateSftpKeyPair::class)->execute($account, 2048);
    expect($keys['public_key'])->toStartWith('ssh-rsa ')->and($keys['private_key'])->toContain('BEGIN PRIVATE KEY');
    expect($account->fresh()->toArray())->not->toHaveKey('ssh_private_key');

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/files/sftp-accounts/'.$foreign->getKey().'/regenerate-keys')->assertNotFound();
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/files/sftp-accounts/'.$account->getKey().'/regenerate-keys', ['ssh_key_bits' => 4096])
        ->assertCreated()->assertJsonPath('data.attributes.one_time_private_key', true)->assertJsonPath('data.attributes.username', 'key-owner');
});
