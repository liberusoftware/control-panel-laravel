<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SocialstreamRegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('socialiteProvidersDataProvider')]
    public function test_users_get_redirected_correctly(string $provider): void
    {
        $this->markTestSkipped('Socialstream is not installed.');
    }

    #[DataProvider('socialiteProvidersDataProvider')]
    public function test_users_can_register_using_socialite_providers(string $socialiteProvider): void
    {
        $this->markTestSkipped('Socialstream is not installed.');
    }

    public static function socialiteProvidersDataProvider(): array
    {
        return [
            ['github'],
            ['google'],
            ['facebook'],
        ];
    }
}
