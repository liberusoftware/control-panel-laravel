<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources\RecordResource\Pages;

use Filament\Resources\Pages\CreateRecord as BaseCreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Dns\Actions\CreateRecord as CreateRecordAction;
use Liberu\ControlPanel\Dns\Models\Zone;
use Liberu\ControlPanel\DnsFilament\Resources\RecordResource;

final class CreateRecord extends BaseCreateRecord
{
    protected static string $resource = RecordResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $zone = Zone::query()->whereKey($data['zone_id'])->where('team_id', auth()->user()?->current_team_id)->firstOrFail();

        return app(CreateRecordAction::class)->execute(array_merge($data, ['team_id' => $zone->team_id]));
    }
}
