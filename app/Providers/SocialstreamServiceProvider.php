<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SocialstreamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Socialstream package is not installed.
        // OAuth connected account functionality is available via ConnectedAccount model directly.
    }
}
