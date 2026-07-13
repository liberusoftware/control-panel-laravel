<?php

namespace Tests\Feature;

use App\Filament\App\Resources\BackupScheduleResource\BackupScheduleResource;
use App\Filament\App\Resources\Domains\DomainResource;
use App\Filament\App\Resources\Users\UserResource;
use App\Models\BackupSchedule;
use App\Models\ConnectedAccount;
use App\Models\Database;
use App\Models\Domain;
use App\Models\EmailAccount;
use App\Models\Server;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_cannot_access_teams_they_do_not_belong_to(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($owner->canAccessTenant($team));
        $this->assertFalse($attacker->canAccessTenant($team));
    }

    public function test_duplicate_user_resource_is_not_available_in_the_app_panel(): void
    {
        $this->assertFalse(UserResource::canAccess());
    }

    public function test_app_resources_only_query_records_owned_by_the_user(): void
    {
        $user = User::factory()->create();
        $ownedDomain = Domain::factory()->create(['user_id' => $user->id]);
        $otherDomain = Domain::factory()->create();

        BackupSchedule::create([
            'domain_id' => $ownedDomain->id,
            'name' => 'Owned schedule',
            'type' => BackupSchedule::TYPE_FULL,
            'frequency' => BackupSchedule::FREQUENCY_DAILY,
            'schedule_time' => '02:00',
            'retention_days' => 30,
        ]);
        BackupSchedule::create([
            'domain_id' => null,
            'name' => 'Global schedule',
            'type' => BackupSchedule::TYPE_FULL,
            'frequency' => BackupSchedule::FREQUENCY_DAILY,
            'schedule_time' => '02:00',
            'retention_days' => 30,
        ]);

        $this->actingAs($user);

        $this->assertSame([$ownedDomain->id], DomainResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame(['Owned schedule'], BackupScheduleResource::getEloquentQuery()->pluck('name')->all());
        $this->assertFalse(DomainResource::getEloquentQuery()->whereKey($otherDomain->id)->exists());
    }

    public function test_virtual_host_api_rejects_user_controlled_privileged_paths(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/virtual-hosts', [
            'hostname' => 'safe.example.com',
            'document_root' => '/etc',
        ])->assertStatus(422)->assertJsonValidationErrors('document_root');
    }

    public function test_related_domain_ids_must_belong_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherDomain = Domain::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/virtual-hosts', [
            'hostname' => 'tenant.example.com',
            'domain_id' => $otherDomain->id,
        ])->assertStatus(422)->assertJsonValidationErrors('domain_id');

        $this->postJson('/api/databases', [
            'name' => 'tenant_db',
            'engine' => 'mysql',
            'domain_id' => $otherDomain->id,
        ])->assertStatus(422)->assertJsonValidationErrors('domain_id');

        $this->postJson('/api/emails', [
            'email_address' => 'owner@example.com',
            'password' => 'a-secure-test-password',
            'domain_id' => $otherDomain->id,
        ])->assertStatus(422)->assertJsonValidationErrors('domain_id');
    }

    public function test_tenant_users_cannot_manage_global_servers_or_choose_system_usernames(): void
    {
        $user = User::factory()->create();
        $domain = Domain::factory()->create(['user_id' => $user->id]);
        $server = Server::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/ssh/servers/{$server->id}/test-connection")
            ->assertForbidden();

        $this->postJson("/api/ssh/domains/{$domain->id}/deploy-key", [
            'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAITestKey',
            'username' => 'root',
        ])->assertStatus(422)->assertJsonValidationErrors('username');

        $this->getJson('/api/services/status')->assertForbidden();
    }

    public function test_detailed_health_metrics_are_not_public(): void
    {
        $this->getJson('/health/detailed')->assertUnauthorized();

        $this->actingAs(User::factory()->create())
            ->getJson('/health/detailed')
            ->assertForbidden();
    }

    public function test_secret_model_attributes_are_never_serialized(): void
    {
        $this->assertContains('password', (new EmailAccount())->getHidden());
        $this->assertContains('external_password', (new Database())->getHidden());
        $this->assertContains('ssl_key', (new Database())->getHidden());
        $this->assertContains('token', (new ConnectedAccount())->getHidden());
        $this->assertContains('secret', (new ConnectedAccount())->getHidden());
        $this->assertContains('refresh_token', (new ConnectedAccount())->getHidden());
    }
}
