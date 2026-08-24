<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationDefinitionResource;

final class ApiAutomationFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'control-panel-api-and-automation-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([AutomationDefinitionResource::class]);
    }

    public function boot(Panel $panel): void {}
}
