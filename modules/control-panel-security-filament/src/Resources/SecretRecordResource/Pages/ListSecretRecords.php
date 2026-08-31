<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources\SecretRecordResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\SecurityFilament\Resources\SecretRecordResource;

final class ListSecretRecords extends ListRecords
{
    protected static string $resource = SecretRecordResource::class;
}
