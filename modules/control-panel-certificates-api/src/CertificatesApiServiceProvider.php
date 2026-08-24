<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesApi;

use Illuminate\Support\ServiceProvider;

final class CertificatesApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
