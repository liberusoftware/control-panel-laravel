<?php

namespace Database\Factories;

use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConnectedAccount>
 */
class ConnectedAccountFactory extends Factory
{
    protected $model = ConnectedAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => $this->faker->randomElement(['github', 'gitlab', 'google', 'facebook']),
            'provider_id' => $this->faker->numerify('########'),
            'token' => Str::random(40),
            'refresh_token' => Str::random(40),
            'expires_at' => null,
        ];
    }
}
