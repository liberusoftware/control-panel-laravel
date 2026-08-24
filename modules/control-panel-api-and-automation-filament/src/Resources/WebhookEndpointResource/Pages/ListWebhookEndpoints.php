<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\WebhookEndpointResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\WebhookEndpointResource;

final class ListWebhookEndpoints extends ListRecords
{
    protected static string $resource = WebhookEndpointResource::class;
}
