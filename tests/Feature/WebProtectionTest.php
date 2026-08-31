<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Actions\AddDirectoryProtectionUser;
use Liberu\ControlPanel\WebHosting\Actions\ConfigureHotlinkProtection;
use Liberu\ControlPanel\WebHosting\Actions\CreateDirectoryProtection;
use Liberu\ControlPanel\WebHosting\Actions\SaveCustomErrorPage;
use Liberu\ControlPanel\WebHosting\Models\CustomErrorPage;
use Liberu\ControlPanel\WebHosting\Models\DirectoryProtectionUser;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\HotlinkProtection;
use Liberu\ControlPanel\WebHosting\Models\MimeType;
use Liberu\ControlPanel\WebHosting\WebHostingServiceProvider;
use Liberu\ControlPanel\WebHostingApi\WebHostingApiServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(WebHostingServiceProvider::class);
    app()->register(WebHostingApiServiceProvider::class);
    $this->artisan('migrate');
});

it('owns validated web protection records and hashes directory passwords', function (): void {
    $domain = Domain::query()->create(['team_id' => 'team-1', 'hostname' => 'example.test', 'status' => 'pending']);
    $hotlink = app(ConfigureHotlinkProtection::class)->execute($domain, ['enabled' => true, 'allowed_domains' => ['example.test'], 'protected_extensions' => ['.jpg'], 'redirect_url' => 'https://example.test/hotlink', 'allow_blank_referrer' => true]);
    $protection = app(CreateDirectoryProtection::class)->execute($domain, ['directory_path' => '/private', 'auth_name' => 'Private files']);
    $user = app(AddDirectoryProtectionUser::class)->execute($protection, 'deploy', 'a-long-password');
    $page = app(SaveCustomErrorPage::class)->execute($domain, ['error_code' => 404, 'custom_content' => '<h1>Missing</h1>']);

    expect($hotlink)->toBeInstanceOf(HotlinkProtection::class)
        ->and($hotlink->protected_extensions)->toBe(['jpg'])
        ->and($protection->users()->count())->toBe(1)
        ->and($user->toArray())->not->toHaveKey('password')
        ->and(Hash::check('a-long-password', $user->password))->toBeTrue()
        ->and($page)->toBeInstanceOf(CustomErrorPage::class);
});

it('rejects invalid web protection input', function (): void {
    $domain = Domain::query()->create(['team_id' => 'team-1', 'hostname' => 'example.test', 'status' => 'pending']);

    expect(fn () => app(CreateDirectoryProtection::class)->execute($domain, ['directory_path' => 'private']))->toThrow(ValidationException::class);
    expect(fn () => app(SaveCustomErrorPage::class)->execute($domain, ['error_code' => 404]))->toThrow(ValidationException::class);
    expect(fn () => app(ConfigureHotlinkProtection::class)->execute($domain, ['redirect_url' => 'not-a-url']))->toThrow(ValidationException::class);
});

it('exposes web protection mutations only within the current team', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $owned = Domain::query()->create(['team_id' => $team->getKey(), 'hostname' => 'owned.test', 'status' => 'pending']);
    $foreign = Domain::query()->create(['team_id' => $otherTeam->getKey(), 'hostname' => 'foreign.test', 'status' => 'pending']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/web-hosting/domains/'.$foreign->getKey().'/hotlink-protection', ['enabled' => true])
        ->assertNotFound();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/web-hosting/domains/'.$owned->getKey().'/directory-protections', ['directory_path' => '/private']);
    $response->assertCreated()->assertJsonPath('data.attributes.directory_path', '/private');
    $protectionId = $response->json('data.id');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/web-hosting/directory-protections/'.$protectionId.'/users', ['username' => 'deploy', 'password' => 'a-long-password'])
        ->assertCreated()
        ->assertJsonMissingPath('data.attributes.password');

    expect(DirectoryProtectionUser::query()->where('directory_protection_id', $protectionId)->exists())->toBeTrue();
});

it('exposes redirect policy lifecycle and MIME metadata through the API', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $domain = Domain::query()->create(['team_id' => $team->getKey(), 'hostname' => 'api-policy.test', 'status' => 'pending']);

    $redirectResponse = $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/web-hosting/domains/'.$domain->getKey().'/redirects', [
        'source' => '/old', 'destination' => '/new', 'is_regex' => true, 'priority' => 5,
    ]);
    $redirectResponse->assertCreated()->assertJsonPath('data.attributes.priority', 5);
    $redirectId = $redirectResponse->json('data.id');

    $this->actingAs($user, 'sanctum')->patchJson('/api/v1/control-panel/web-hosting/redirects/'.$redirectId, ['status_code' => 308])
        ->assertOk()->assertJsonPath('data.attributes.redirect_type', '308');
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/control-panel/web-hosting/domains/'.$domain->getKey().'/mime-types', ['extension' => '.webp', 'mime_type' => 'image/webp'])
        ->assertCreated()->assertJsonPath('data.attributes.extension', '.webp');

    expect(MimeType::query()->where('domain_id', $domain->getKey())->count())->toBe(1);

    $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/control-panel/web-hosting/redirects/'.$redirectId)->assertNoContent();
});
