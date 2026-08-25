<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\PhpConfiguration;

final class SavePhpConfiguration
{
    /** @param array<string, mixed> $attributes */
    public function execute(Domain $domain, array $attributes): PhpConfiguration
    {
        $version = (string) ($attributes['php_version'] ?? '');
        if (! in_array($version, PhpConfiguration::getSupportedVersions(), true)) {
            throw ValidationException::withMessages(['php_version' => 'The selected PHP version is not supported.']);
        }

        $numeric = ['memory_limit', 'upload_max_filesize', 'post_max_size', 'max_execution_time', 'max_input_time', 'max_input_vars'];
        foreach ($numeric as $field) {
            if (isset($attributes[$field]) && (int) $attributes[$field] < 1) {
                throw ValidationException::withMessages([$field => 'This value must be greater than zero.']);
            }
        }

        return PhpConfiguration::query()->updateOrCreate(
            ['domain_id' => $domain->getKey()],
            array_merge($attributes, ['team_id' => $domain->team_id, 'php_version' => $version]),
        );
    }
}
