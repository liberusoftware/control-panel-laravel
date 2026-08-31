<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources\DeliveryDiagnosticResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MailFilament\Resources\DeliveryDiagnosticResource;

final class ListDeliveryDiagnostics extends ListRecords
{
    protected static string $resource = DeliveryDiagnosticResource::class;
}
