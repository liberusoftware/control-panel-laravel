<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\WebhookEndpointResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\WebhookEndpointResource;

final class EditWebhookEndpoint extends EditRecord
{
    protected static string $resource = WebhookEndpointResource::class;
}
