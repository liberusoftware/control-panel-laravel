<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\GitDeploymentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\WebHostingFilament\Resources\GitDeploymentResource;

final class ListGitDeployments extends ListRecords
{
    protected static string $resource = GitDeploymentResource::class;
}
