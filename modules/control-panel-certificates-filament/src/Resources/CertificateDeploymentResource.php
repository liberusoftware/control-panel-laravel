<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Certificates\Models\CertificateDeployment;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateDeploymentResource\Pages\ListCertificateDeployments;

final class CertificateDeploymentResource extends CertificateLifecycleResource
{
    protected static ?string $model = CertificateDeployment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string|\UnitEnum|null $navigationGroup = 'Certificates';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('certificate_id')->label('Certificate')->searchable(),
            TextColumn::make('target_type')->badge(),
            TextColumn::make('target_id')->label('Target')->searchable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('deployed_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCertificateDeployments::route('/')];
    }
}
