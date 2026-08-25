<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsFilament\Resources\HostingPackageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Accounts\Actions\CreateHostingPackage as CreateHostingPackageAction;
use Liberu\ControlPanel\AccountsFilament\Resources\HostingPackageResource;

final class CreateHostingPackage extends CreateRecord
{
    protected static string $resource = HostingPackageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateHostingPackageAction::class)->execute($data);
    }
}
