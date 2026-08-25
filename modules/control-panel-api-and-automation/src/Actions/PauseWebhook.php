<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ApiAutomation\Models\WebhookEndpoint;

final class PauseWebhook
{
    public function execute(WebhookEndpoint $webhook): WebhookEndpoint
    {
        if ($webhook->status !== 'active') {
            throw ValidationException::withMessages(['webhook' => 'Only active webhooks can be paused.']);
        }

        return DB::transaction(function () use ($webhook): WebhookEndpoint {
            $webhook->forceFill(['status' => 'paused'])->save();

            return $webhook->refresh();
        });
    }
}
