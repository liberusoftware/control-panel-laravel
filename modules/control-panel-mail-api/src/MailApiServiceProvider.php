<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailApi;

use Illuminate\Support\ServiceProvider;

final class MailApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
