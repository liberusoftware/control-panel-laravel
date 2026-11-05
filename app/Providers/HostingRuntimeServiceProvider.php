<?php

namespace App\Providers;

use App\Hosting\Kubernetes\VerifiedReverbRedisClientFactory;
use Illuminate\Support\ServiceProvider;
use Laravel\Reverb\Servers\Reverb\Publishing\RedisClientFactory;

class HostingRuntimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RedisClientFactory::class, VerifiedReverbRedisClientFactory::class);
    }
}
