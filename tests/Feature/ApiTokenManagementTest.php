<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Foundation\ApiAccess\Support\TokenPolicy;
use Liberu\Foundation\SessionsDevicesFilament\Pages\AccountSecurity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate');
});

it('creates bounded personal access tokens and only returns plaintext once', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $page = app(AccountSecurity::class);
    $page->tokenName = 'automation client';
    $page->tokenAbilities = ['read', 'update'];

    $page->createToken(app(TokenPolicy::class));

    $stored = $user->tokens()->first();
    expect($stored)->not->toBeNull()
        ->and($stored->name)->toBe('automation client')
        ->and($stored->abilities)->toBe(['read', 'update'])
        ->and($stored->token)->not->toBe($page->newToken)
        ->and($page->tokens)->toHaveCount(1)
        ->and($page->newToken)->not->toBeNull();
});

it('defaults a token without selected abilities to read-only', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $page = app(AccountSecurity::class);
    $page->tokenName = 'read-only client';
    $page->tokenAbilities = [];

    $page->createToken(app(TokenPolicy::class));

    expect($user->tokens()->first()->abilities)->toBe(['read']);
});

it('revokes only the authenticated users token', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $userToken = $user->createToken('own token')->accessToken;
    $otherToken = $otherUser->createToken('other token')->accessToken;
    $this->actingAs($user);
    $page = app(AccountSecurity::class);

    $page->revokeToken((string) $otherToken->getKey());
    expect($otherUser->tokens()->whereKey($otherToken->getKey())->exists())->toBeTrue();

    $page->revokeToken((string) $userToken->getKey());
    expect($user->tokens()->whereKey($userToken->getKey())->exists())->toBeFalse();
});
