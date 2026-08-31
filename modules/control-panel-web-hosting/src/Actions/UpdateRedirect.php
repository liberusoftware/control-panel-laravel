<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\Redirect;

final class UpdateRedirect
{
    /** @param array<string, mixed> $attributes */
    public function execute(Redirect $redirect, array $attributes): Redirect
    {
        $source = trim((string) ($attributes['source'] ?? $redirect->source));
        $destination = trim((string) ($attributes['destination'] ?? $redirect->destination));
        $code = (int) ($attributes['status_code'] ?? $redirect->status_code);
        if ($source === '' || $destination === '' || ! in_array($code, [301, 302, 307, 308], true)) {
            throw ValidationException::withMessages(['redirect' => 'A source, destination, and supported redirect status are required.']);
        }

        $redirect->fill([
            'source' => $source,
            'destination' => $destination,
            'status_code' => $code,
            'active' => (bool) ($attributes['active'] ?? $redirect->active),
            'source_path' => $source,
            'destination_url' => $destination,
            'redirect_type' => (string) $code,
            'match_query_string' => (bool) ($attributes['match_query_string'] ?? $redirect->match_query_string),
            'is_regex' => (bool) ($attributes['is_regex'] ?? $redirect->is_regex),
            'priority' => (int) ($attributes['priority'] ?? $redirect->priority),
        ])->save();

        return $redirect->refresh();
    }
}
