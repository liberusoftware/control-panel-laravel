<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Certificates\Actions\RecordCertificateOperation;
use Liberu\ControlPanel\Certificates\Actions\RegisterAcmeAccount;

final class CertificatesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    public function register(): void
    {
        $this->app->scoped(RegisterAcmeAccount::class);
        $this->app->scoped(RecordCertificateOperation::class);
    }
}
