<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Accounts\Models\HostingPackage;

final class UpdateHostingPackage
{
    /** @param array<string, mixed> $attributes */
    public function execute(HostingPackage $package, array $attributes): HostingPackage
    {
        $name = trim((string) ($attributes['name'] ?? $package->name));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'A package name is required.']);
        }

        $package->forceFill([
            'name' => $name,
            'limits' => $attributes['limits'] ?? $package->limits,
            'features' => $attributes['features'] ?? $package->features,
            'active' => $attributes['active'] ?? $package->active,
        ])->save();

        return $package->refresh();
    }
}
