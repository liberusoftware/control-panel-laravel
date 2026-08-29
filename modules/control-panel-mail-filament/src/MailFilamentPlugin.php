<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\MailFilament\Resources\DkimKeyResource;
use Liberu\ControlPanel\MailFilament\Resources\MailAccountResource;
use Liberu\ControlPanel\MailFilament\Resources\MailAliasResource;
use Liberu\ControlPanel\MailFilament\Resources\MailDomainResource;
use Liberu\ControlPanel\MailFilament\Resources\MailRouteResource;

final class MailFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'control-panel-mail-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([MailAccountResource::class, MailAliasResource::class, DkimKeyResource::class, MailDomainResource::class, MailRouteResource::class]);
    }

    public function boot(Panel $panel): void {}
}
