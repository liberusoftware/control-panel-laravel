<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Actions\RegisterMailDomain;
use Liberu\ControlPanel\Mail\MailServiceProvider;
use Liberu\ControlPanel\Mail\Models\MailDomain;
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
