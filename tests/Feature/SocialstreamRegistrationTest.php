<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JoelButcher\Socialstream\Providers;
use JoelButcher\Socialstream\Socialstream;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SocialstreamRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_socialstream_config_has_social_media_providers(): void
    {
        $providers = config('socialstream.providers');

        $this->assertNotEmpty($providers);

        $expectedProviders = [
            'bitbucket',
            'facebook',
            'github',
            'gitlab',
            'google',
            'linkedin',
            'linkedin-openid',
            'slack',
            'twitter-oauth-2',
        ];

        $configuredIds = array_map(fn ($p) => is_array($p) ? ($p['id'] ?? $p) : $p, $providers);

        foreach ($expectedProviders as $expected) {
            $this->assertContains(
                $expected,
                $configuredIds,
                "Expected provider '{$expected}' to be configured in socialstream config."
            );
        }
    }

    #[DataProvider('socialiteProvidersDataProvider')]
    public function test_users_get_redirected_correctly(string $provider): void
    {
        $response = $this->get(route('oauth.redirect', ['provider' => $provider]));

        $response->assertRedirectContains($provider);
    }

    #[DataProvider('socialiteProvidersDataProvider')]
    public function test_users_can_register_using_socialite_providers(string $socialiteProvider): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('provider-user-id-123');
        $socialiteUser->shouldReceive('getName')->andReturn('Test User');
        $socialiteUser->shouldReceive('getNickname')->andReturn('testuser');
        $socialiteUser->shouldReceive('getEmail')->andReturn('oauth-test@example.com');
        $socialiteUser->shouldReceive('getAvatar')->andReturn(null);
        $socialiteUser->shouldReceive('approvedScopes')->andReturn([]);
        // token, tokenSecret, refreshToken and expiresIn are public properties on SocialiteUser
        $socialiteUser->token = 'fake-access-token';
        $socialiteUser->tokenSecret = null;
        $socialiteUser->refreshToken = null;
        $socialiteUser->expiresIn = 3600;

        Socialite::shouldReceive('driver')
            ->with($socialiteProvider)
            ->andReturnSelf();

        Socialite::shouldReceive('stateless')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($socialiteUser);

        $response = $this->get(route('oauth.callback', ['provider' => $socialiteProvider]));

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'oauth-test@example.com',
        ]);
    }

    public static function socialiteProvidersDataProvider(): array
    {
        return [
            'github'         => ['github'],
            'google'         => ['google'],
            'facebook'       => ['facebook'],
            'gitlab'         => ['gitlab'],
            'bitbucket'      => ['bitbucket'],
            'linkedin-openid' => ['linkedin-openid'],
            'slack'          => ['slack'],
        ];
    }
}
