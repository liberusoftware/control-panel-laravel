<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\GitDeploymentResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\WebHostingFilament\Resources\GitDeploymentResource;

final class EditGitDeployment extends EditRecord
{
    protected static string $resource = GitDeploymentResource::class;
}
