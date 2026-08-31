<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Certificates\Models\CertificateOperation;
use Liberu\ControlPanel\CertificatesFilament\Resources\CertificateOperationResource\Pages\ListCertificateOperations;

final class CertificateOperationResource extends Resource
{
    protected static ?string $model = CertificateOperation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|\UnitEnum|null $navigationGroup = 'Certificates';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('certificate_id')->label('Certificate')->searchable(),
            TextColumn::make('operation')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('completed_at')->dateTime()->sortable(),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListCertificateOperations::route('/')];
    }
}
