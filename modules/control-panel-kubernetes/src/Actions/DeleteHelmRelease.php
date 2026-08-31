<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Actions;

use Liberu\ControlPanel\Kubernetes\Models\HelmRelease;

final class DeleteHelmRelease
{
    public function execute(HelmRelease $release): void
    {
        $release->delete();
    }
}
