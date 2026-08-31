<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\OrchestrationRunResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\OrchestrationRunResource;

final class ListOrchestrationRuns extends ListRecords
{
    protected static string $resource = OrchestrationRunResource::class;
}
