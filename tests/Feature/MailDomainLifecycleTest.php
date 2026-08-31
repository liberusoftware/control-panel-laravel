<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Actions\ConfigureMailAuthentication;
use Liberu\ControlPanel\Mail\Actions\CreateMailRoute;
use Liberu\ControlPanel\Mail\Actions\RegisterMailDomain;
use Liberu\ControlPanel\Mail\MailServiceProvider;
use Liberu\ControlPanel\Mail\Models\MailDomain;
use Liberu\ControlPanel\Mail\Models\MailRoute;
use Liberu\ControlPanel\MailApi\MailApiServiceProvider;
use Liberu\ControlPanel\MailLivewire\Components\MailFeatureInventory;
use Liberu\ControlPanel\MailLivewire\MailLivewireServiceProvider;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(MailServiceProvider::class);
    app()->register(MailApiServiceProvider::class);
    app()->register(MailLivewireServiceProvider::class);
    $this->artisan('migrate');
});

it('registers normalized tenant mail domains and rejects invalid domains', function (): void {
    $domain = app(RegisterMailDomain::class)->execute(['team_id' => 'team-1', 'domain' => 'Example.TEST']);

    expect($domain->domain)->toBe('example.test')->and($domain->status)->toBe('active');
    expect(fn () => app(RegisterMailDomain::class)->execute(['team_id' => 'team-1', 'domain' => 'not a domain']))
        ->toThrow(ValidationException::class);
});

it('generates tenant mail authentication records without exposing private keys', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $domain = app(RegisterMailDomain::class)->execute(['team_id' => $team->getKey(), 'domain' => 'auth.example.test']);

    $configured = app(ConfigureMailAuthentication::class)->execute($domain, [
        'dkim_selector' => 'mail', 'dmarc_policy' => 'quarantine', 'dmarc_percentage' => 80,
        'dmarc_rua_email' => 'reports@example.test', 'dmarc_ruf_email' => 'forensics@example.test',
    ]);

    expect($configured->spf['record'])->toBe('v=spf1 mx a ~all')
        ->and($configured->dkim['selector'])->toBe('mail')
        ->and($configured->dkim['dns_record'])->toStartWith('v=DKIM1; k=rsa; p=')
        ->and($configured->dmarc['record'])->toContain('p=quarantine', 'pct=80')
        ->and($configured->toArray())->not->toHaveKey('private_key');

    app()->register(MailApiServiceProvider::class);
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/mail/authentication/configure', ['mail_domain_id' => $domain->getKey()])
        ->assertOk()
        ->assertJsonPath('data.attributes.domain', 'auth.example.test');
});

it('exposes a current-team mail domain through the API and Livewire inventory', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    app(RegisterMailDomain::class)->execute(['team_id' => $otherTeam->getKey(), 'domain' => 'other.test']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/mail/domains', ['domain' => 'example.test'])
        ->assertCreated()
        ->assertJsonPath('data.attributes.domain', 'example.test');

    $this->actingAs($user);
    $component = app(MailFeatureInventory::class);
    expect($component->render()->getData()['domains'])->toHaveCount(1)
        ->and(MailDomain::query()->where('team_id', $team->getKey())->count())->toBe(1);
});

it('creates tenant-scoped mail routes through the API and inventory', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);

    app(CreateMailRoute::class)->execute([
        'team_id' => $otherTeam->getKey(),
        'domain' => 'other.test',
        'source_pattern' => '*@other.test',
        'destination' => 'ops@example.test',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/control-panel/mail/routes', [
            'domain' => 'Example.TEST',
            'source_pattern' => '*@example.test',
            'destination' => 'ops@example.test',
            'priority' => 10,
        ])
        ->assertCreated()
        ->assertJsonPath('data.attributes.domain', 'example.test')
        ->assertJsonPath('data.attributes.priority', 10);

    $this->actingAs($user);
    $routes = app(MailFeatureInventory::class)->render()->getData()['routes'];

    expect($routes)->toHaveCount(1)
        ->and($routes->first()->team_id)->toBe((string) $team->getKey())
        ->and(MailRoute::query()->where('team_id', $otherTeam->getKey())->count())->toBe(1);
});

it('rejects mail routes without a tenant or with an invalid domain', function (): void {
    expect(fn () => app(CreateMailRoute::class)->execute([
        'domain' => 'example.test',
        'source_pattern' => '*@example.test',
        'destination' => 'ops@example.test',
    ]))->toThrow(ValidationException::class);

    expect(fn () => app(CreateMailRoute::class)->execute([
        'team_id' => 'team-1',
        'domain' => 'not a domain',
        'source_pattern' => '*@example.test',
        'destination' => 'ops@example.test',
    ]))->toThrow(ValidationException::class);
});
