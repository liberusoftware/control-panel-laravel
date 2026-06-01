<?php

namespace App\Providers;

use App\Actions\Socialstream\CreateConnectedAccount;
use App\Actions\Socialstream\CreateUserFromProvider;
use App\Actions\Socialstream\GenerateRedirectForProvider;
use App\Actions\Socialstream\HandleInvalidState;
use App\Actions\Socialstream\ResolveSocialiteUser;
use App\Actions\Socialstream\SetUserPassword;
use App\Actions\Socialstream\UpdateConnectedAccount;
use Illuminate\Support\ServiceProvider;
use JoelButcher\Socialstream\Socialstream;

class SocialstreamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Socialstream::createConnectedAccountsUsing(CreateConnectedAccount::class);
        Socialstream::createUsersFromProviderUsing(CreateUserFromProvider::class);
        Socialstream::generateRedirectForProviderUsing(GenerateRedirectForProvider::class);
        Socialstream::handleInvalidStateUsing(HandleInvalidState::class);
        Socialstream::resolvesSocialiteUsersUsing(ResolveSocialiteUser::class);
        Socialstream::setUserPasswordsUsing(SetUserPassword::class);
        Socialstream::updateConnectedAccountsUsing(UpdateConnectedAccount::class);
    }
}
