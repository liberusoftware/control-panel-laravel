<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Dns\Actions\ArchiveZone;
use Liberu\ControlPanel\Dns\Actions\CheckDnsPropagation;
use Liberu\ControlPanel\Dns\Actions\CheckDnsResolution;
use Liberu\ControlPanel\Dns\Actions\CreateRecord;
use Liberu\ControlPanel\Dns\Actions\DeleteRecord;
use Liberu\ControlPanel\Dns\Actions\RecordDnsCheck;
use Liberu\ControlPanel\Dns\Actions\RegisterDnsFeature;
use Liberu\ControlPanel\Dns\Actions\SuspendZone;
use Liberu\ControlPanel\Dns\Actions\ValidateRecord;
use Liberu\ControlPanel\Dns\Contracts\DnsResolver;
use Liberu\ControlPanel\Dns\Support\NativeDnsResolver;

final class DnsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(ArchiveZone::class);
        $this->app->scoped(CheckDnsPropagation::class);
        $this->app->scoped(CheckDnsResolution::class);
        $this->app->scoped(CreateRecord::class);
        $this->app->scoped(DeleteRecord::class);
        $this->app->scoped(RecordDnsCheck::class);
        $this->app->scoped(RegisterDnsFeature::class);
        $this->app->scoped(SuspendZone::class);
        $this->app->scoped(ValidateRecord::class);
        $this->app->bind(DnsResolver::class, NativeDnsResolver::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
