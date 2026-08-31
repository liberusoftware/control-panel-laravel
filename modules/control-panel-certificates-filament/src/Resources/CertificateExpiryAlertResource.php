<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Certificates\Models\CertificateExpiryAlert;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateExpiryAlertResource\Pages\ListCertificateExpiryAlerts;

final class CertificateExpiryAlertResource extends CertificateLifecycleResource
{
    protected static ?string $model = CertificateExpiryAlert::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string|\UnitEnum|null $navigationGroup = 'Certificates';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('certificate_id')->label('Certificate')->searchable(),
            TextColumn::make('threshold_days')->numeric(),
            TextColumn::make('status')->badge(),
            TextColumn::make('notified_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCertificateExpiryAlerts::route('/')];
    }
}
