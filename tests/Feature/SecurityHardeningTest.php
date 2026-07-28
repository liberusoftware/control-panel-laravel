<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\GitDeployment;
use App\Models\Team;
use App\Models\User;
use App\Models\Website;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_only_access_teams_they_belong_to(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = Team::factory()->create();

        $this->assertTrue($user->canAccessTenant($user->currentTeam));
        $this->assertFalse($user->canAccessTenant($otherTeam));
    }

    public function test_only_verified_super_admins_can_access_admin_panel(): void
    {
        $panel = Panel::make()->id('admin');
        $user = User::factory()->create();

        $this->assertFalse($user->canAccessPanel($panel));

        Role::findOrCreate('super_admin', 'web');
        $user->assignRole('super_admin');

        $this->assertTrue($user->fresh()->canAccessPanel($panel));

        $user->forceFill(['email_verified_at' => null])->save();

        $this->assertFalse($user->fresh()->canAccessPanel($panel));
    }

    public function test_read_only_token_cannot_create_resources(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['read']);

        $this->getJson('/api/websites')->assertOk();
        $this->postJson('/api/websites', [
            'name' => 'Blocked',
            'domain' => 'blocked.example.com',
        ])->assertForbidden();
    }

    public function test_email_account_cannot_reference_another_users_domain(): void
    {
        $user = User::factory()->create();
        $otherDomain = Domain::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/emails', [
            'domain_id' => $otherDomain->id,
            'email_address' => 'attacker@example.com',
            'password' => 'correct-horse-battery-staple',
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('email_accounts', [
            'email_address' => 'attacker@example.com',
        ]);
    }

    public function test_api_pagination_is_capped_at_one_hundred(): void
    {
        $user = User::factory()->create();
        Website::factory()->count(105)->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/websites?per_page=10000')
            ->assertOk()
            ->assertJsonPath('per_page', 100)
            ->assertJsonCount(100, 'data');
    }

    public function test_git_deployment_secrets_are_encrypted_at_rest(): void
    {
        $domain = Domain::factory()->create();
        $deployment = GitDeployment::factory()->create([
            'domain_id' => $domain->id,
            'deploy_key' => 'private-key-value',
            'webhook_secret' => 'webhook-secret-value',
        ]);

        $stored = DB::table('git_deployments')->where('id', $deployment->id)->first();

        $this->assertNotSame('private-key-value', $stored->deploy_key);
        $this->assertNotSame('webhook-secret-value', $stored->webhook_secret);
        $this->assertSame('private-key-value', $deployment->fresh()->deploy_key);
        $this->assertSame('webhook-secret-value', $deployment->fresh()->webhook_secret);
    }

    public function test_detailed_health_information_requires_authentication(): void
    {
        $this->get('/health/detailed')->assertRedirect();
    }
}
