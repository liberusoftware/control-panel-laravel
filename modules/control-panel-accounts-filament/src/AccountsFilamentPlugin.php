<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\AccountsFilament\Resources\AccountResource;
use Liberu\ControlPanel\AccountsFilament\Resources\HostingPackageResource;
use Liberu\ControlPanel\AccountsFilament\Resources\AccountDelegationResource;

final class AccountsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'control-panel-accounts-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([AccountResource::class, HostingPackageResource::class, AccountDelegationResource::class]);
    }

    public function boot(Panel $panel): void {}
}
