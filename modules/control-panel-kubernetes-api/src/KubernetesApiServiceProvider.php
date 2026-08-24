<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesApi;

use Illuminate\Support\ServiceProvider;

final class KubernetesApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
