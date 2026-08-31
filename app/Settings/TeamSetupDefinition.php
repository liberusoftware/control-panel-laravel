<?php

declare(strict_types=1);

namespace App\Settings;

use Liberu\Foundation\Settings\Contracts\SettingDefinition;

final class TeamSetupDefinition implements SettingDefinition
{
    public function key(): string
    {
        return 'team.setup';
    }

    public function validate(mixed $value): bool
    {
        return is_array($value)
            && isset($value['completed_steps'])
            && is_array($value['completed_steps'])
            && isset($value['integrations'])
            && is_array($value['integrations']);
    }

    public function secret(): bool
    {
        return true;
    }
}
