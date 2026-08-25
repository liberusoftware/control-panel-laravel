<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\WebHosting\Actions\ActivateDomain;
use Liberu\ControlPanel\WebHosting\Actions\CreateDomain;
use Liberu\ControlPanel\WebHosting\Actions\CreateVirtualHost;
use Liberu\ControlPanel\WebHosting\Actions\CreateRedirect;
use Liberu\ControlPanel\WebHosting\Actions\RequestCertificate;
use Liberu\ControlPanel\WebHosting\Actions\RegisterHostingResource;
use Liberu\ControlPanel\WebHosting\Actions\RegisterGitDeployment;
use Liberu\ControlPanel\WebHosting\Actions\SavePhpConfiguration;
use Liberu\ControlPanel\WebHosting\Queries\ListDomains;
use Liberu\ControlPanel\WebHosting\Queries\ListGitDeployments;

final class WebHostingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(ActivateDomain::class);
        $this->app->scoped(CreateDomain::class);
        $this->app->scoped(CreateVirtualHost::class);
        $this->app->scoped(ListDomains::class);
        $this->app->scoped(CreateRedirect::class);
        $this->app->scoped(RequestCertificate::class);
        $this->app->scoped(RegisterHostingResource::class);
        $this->app->scoped(RegisterGitDeployment::class);
        $this->app->scoped(ListGitDeployments::class);
        $this->app->scoped(SavePhpConfiguration::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
