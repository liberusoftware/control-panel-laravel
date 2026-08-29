<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources\MaintenanceWindowResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Monitoring\Actions\UpdateMaintenanceWindow;
use Liberu\ControlPanel\MonitoringFilament\Resources\MaintenanceWindowResource;

final class EditMaintenanceWindow extends EditRecord
{
    protected static string $resource = MaintenanceWindowResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateMaintenanceWindow::class)->execute($record, $data);
    }
}
