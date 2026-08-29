<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources\RecordResource\Pages;

use Filament\Resources\Pages\EditRecord as BaseEditRecord;
use Liberu\ControlPanel\DnsFilament\Resources\RecordResource;

final class EditRecord extends BaseEditRecord
{
    protected static string $resource = RecordResource::class;
}
