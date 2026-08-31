<?php

use App\Filament\App\Pages\AccountSetup;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Foundation\Settings\Services\ScopedSettings;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

it('saves the current team profile and resumes at integrations', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->getKey(), 'name' => 'New workspace']);
    $user->forceFill(['current_team_id' => $team->getKey()])->save();

    Livewire::actingAs($user)
        ->test(AccountSetup::class)
        ->set('teamName', 'Launch workspace')
        ->set('timezone', 'Europe/London')
        ->call('saveProfile')
        ->assertSet('step', 2)
        ->assertHasNoErrors();

    expect($team->refresh()->name)->toBe('Launch workspace')
        ->and(app(ScopedSettings::class)->resolve('team.setup', ['team' => $team->getKey()])['timezone'])->toBe('Europe/London');
});

it('encrypts saved integration credentials and clears secret inputs', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->getKey()]);
    $user->forceFill(['current_team_id' => $team->getKey()])->save();

    Livewire::actingAs($user)
        ->test(AccountSetup::class)
        ->set('githubClientId', 'github-client')
        ->set('githubClientSecret', 'github-secret')
        ->set('stripeSecret', 'stripe-secret')
        ->call('saveIntegrations')
        ->assertSet('step', 3)
        ->assertSet('githubClientSecret', '')
        ->assertSet('stripeSecret', '')
        ->assertHasNoErrors();

    $stored = DB::table('scoped_settings')->where('scope_type', 'team')->where('scope_id', (string) $team->getKey())->where('key', 'team.setup')->value('value');

    expect($stored)->not->toContain('github-secret')->not->toContain('stripe-secret')
        ->and(app(ScopedSettings::class)->resolve('team.setup', ['team' => $team->getKey()])['integrations']['github_client_secret'])->toBe('github-secret');
});
