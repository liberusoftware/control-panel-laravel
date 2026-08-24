<?php

use App\Models\User;

it('falls back to the configured profile photo URL for non-URL paths', function () {
    $user = User::factory()->make(['profile_photo_path' => 'avatars/user.jpg']);

    expect($user->profile_photo_url)->toBeString();
});

it('preserves an absolute profile photo URL', function () {
    $url = 'https://cdn.example.test/avatar.png';
    $user = User::factory()->make(['profile_photo_path' => $url]);

    expect($user->profile_photo_url)->toBe($url);
});
