<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\ApiCredentialResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\ApiCredentialResource;

final class EditApiCredential extends EditRecord
{
    protected static string $resource = ApiCredentialResource::class;
}
