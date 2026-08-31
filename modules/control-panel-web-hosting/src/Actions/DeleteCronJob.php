<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Liberu\ControlPanel\WebHosting\Models\CronJob;

final class DeleteCronJob
{
    public function execute(CronJob $job): void
    {
        $job->delete();
    }
}
