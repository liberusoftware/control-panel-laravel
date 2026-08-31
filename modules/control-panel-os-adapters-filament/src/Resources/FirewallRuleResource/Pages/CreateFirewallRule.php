<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources\FirewallRuleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\OsAdapters\Actions\CreateFirewallRule as CreateFirewallRuleAction;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\FirewallRuleResource;

final class CreateFirewallRule extends CreateRecord
{
    protected static string $resource = FirewallRuleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return app(CreateFirewallRuleAction::class)->execute(array_merge($data, ['team_id' => $teamId]));
    }
}
