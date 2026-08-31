<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\ApiCredentialResource;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationCommandResource;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationDefinitionResource;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationScheduleResource;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\AutomationTemplateResource;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\BillingProvisioningEventResource;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\OrchestrationRunResource;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\WebhookEndpointResource;

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
        $panel->resources([ApiCredentialResource::class, AutomationDefinitionResource::class, WebhookEndpointResource::class, AutomationTemplateResource::class, AutomationScheduleResource::class, AutomationCommandResource::class, BillingProvisioningEventResource::class, OrchestrationRunResource::class]);
    }

    public function boot(Panel $panel): void {}
}
