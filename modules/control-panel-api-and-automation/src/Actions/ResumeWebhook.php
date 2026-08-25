<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ApiAutomation\Models\WebhookEndpoint;

final class ResumeWebhook
{
    public function execute(WebhookEndpoint $webhook): WebhookEndpoint
    {
        if (! in_array($webhook->status, ['paused', 'failed'], true)) {
            throw ValidationException::withMessages(['webhook' => 'Only paused or failed webhooks can be resumed.']);
        }

        return DB::transaction(function () use ($webhook): WebhookEndpoint {
            $webhook->forceFill(['status' => 'active', 'failure_count' => 0])->save();

            return $webhook->refresh();
        });
    }
}
