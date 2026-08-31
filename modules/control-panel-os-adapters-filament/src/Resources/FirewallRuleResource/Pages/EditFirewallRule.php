<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources\FirewallRuleResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\OsAdapters\Actions\UpdateFirewallRule as UpdateFirewallRuleAction;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\FirewallRuleResource;

final class EditFirewallRule extends EditRecord
{
    protected static string $resource = FirewallRuleResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateFirewallRuleAction::class)->execute($record, $data);
    }
}
