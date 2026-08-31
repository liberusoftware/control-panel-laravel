<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources\MfaRbacPolicyResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\SecurityFilament\Resources\MfaRbacPolicyResource;

final class ListMfaRbacPolicies extends ListRecords
{
    protected static string $resource = MfaRbacPolicyResource::class;
}
