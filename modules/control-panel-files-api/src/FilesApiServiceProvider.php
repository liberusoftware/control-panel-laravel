<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesApi;

use Illuminate\Support\ServiceProvider;

final class FilesApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
