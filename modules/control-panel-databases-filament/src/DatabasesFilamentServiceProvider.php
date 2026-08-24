<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;

final class DatabasesFilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Filament::registerRenderHook('panels::body.end', static fn (): string => '');
    }
}
