<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Dns\Actions\CreateRecord;
use Liberu\ControlPanel\Dns\Actions\RecordDnsCheck;

final class DnsServiceProvider extends ServiceProvider
{
    public function register(): void { $this->app->scoped(CreateRecord::class); $this->app->scoped(RecordDnsCheck::class); }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
