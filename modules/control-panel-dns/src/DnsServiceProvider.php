<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Dns\Actions\ArchiveZone;
use Liberu\ControlPanel\Dns\Actions\CreateRecord;
use Liberu\ControlPanel\Dns\Actions\RecordDnsCheck;
use Liberu\ControlPanel\Dns\Actions\RegisterDnsFeature;
use Liberu\ControlPanel\Dns\Actions\SuspendZone;

final class DnsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(ArchiveZone::class);
        $this->app->scoped(CreateRecord::class);
        $this->app->scoped(RecordDnsCheck::class);
        $this->app->scoped(RegisterDnsFeature::class);
        $this->app->scoped(SuspendZone::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
