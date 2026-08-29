<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Certificates\Actions\CheckCertificateExpiry;
use Liberu\ControlPanel\Certificates\Actions\ExpireCertificate;
use Liberu\ControlPanel\Certificates\Actions\RecordCertificateOperation;
use Liberu\ControlPanel\Certificates\Actions\RegisterAcmeAccount;
use Liberu\ControlPanel\Certificates\Actions\RegisterCertificateLifecycle;
use Liberu\ControlPanel\Certificates\Actions\RequestCertificateDeployment;
use Liberu\ControlPanel\Certificates\Actions\RequestCertificateRenewal;
use Liberu\ControlPanel\Certificates\Actions\UpdateCertificate;

final class CertificatesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    public function register(): void
    {
        $this->app->scoped(RegisterAcmeAccount::class);
        $this->app->scoped(RegisterCertificateLifecycle::class);
        $this->app->scoped(RecordCertificateOperation::class);
        $this->app->scoped(CheckCertificateExpiry::class);
        $this->app->scoped(ExpireCertificate::class);
        $this->app->scoped(RequestCertificateDeployment::class);
        $this->app->scoped(RequestCertificateRenewal::class);
        $this->app->scoped(UpdateCertificate::class);
    }
}
