<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources\DnsCheckResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\DnsFilament\Resources\DnsCheckResource;

final class EditDnsCheck extends EditRecord
{
    protected static string $resource = DnsCheckResource::class;
}
