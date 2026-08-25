<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\GitDeployment;

final class RequestGitDeployment
{
    public function execute(GitDeployment $deployment): GitDeployment
    {
        if ($deployment->isDeploying() || $deployment->status === 'queued') {
            throw ValidationException::withMessages(['deployment' => 'This deployment is already in progress.']);
        }

        $deployment->forceFill([
            'status' => 'queued',
            'deployment_log' => trim((string) $deployment->deployment_log)."\nDeployment queued at ".now()->toIso8601String(),
        ])->save();

        return $deployment->refresh();
    }
}
