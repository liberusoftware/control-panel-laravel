<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Models\MailAlias;

final class UpdateMailAlias
{
    public function execute(MailAlias $alias, array $attributes): MailAlias
    {
        $domain = trim((string) ($attributes['domain'] ?? $alias->domain));
        $address = trim((string) ($attributes['address'] ?? $alias->address));
        $destinations = array_values(array_filter(array_map('trim', $attributes['destinations'] ?? $alias->destinations ?? [])));
        if (! filter_var($address.'@'.$domain, FILTER_VALIDATE_EMAIL) || $destinations === [] || array_filter($destinations, fn (string $destination): bool => filter_var($destination, FILTER_VALIDATE_EMAIL) === false) !== []) {
            throw ValidationException::withMessages(['alias' => 'A valid alias and at least one destination are required.']);
        }

        $alias->forceFill(['domain' => $domain, 'address' => $address, 'destinations' => $destinations, 'active' => $attributes['active'] ?? $alias->active])->save();

        return $alias->refresh();
    }
}
