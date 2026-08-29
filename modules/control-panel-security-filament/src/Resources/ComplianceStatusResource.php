<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Security\Models\ComplianceStatus;
use Liberu\ControlPanel\SecurityFilament\Resources\ComplianceStatusResource\Pages\CreateComplianceStatus;
use Liberu\ControlPanel\SecurityFilament\Resources\ComplianceStatusResource\Pages\EditComplianceStatus;
use Liberu\ControlPanel\SecurityFilament\Resources\ComplianceStatusResource\Pages\ListComplianceStatuses;

final class ComplianceStatusResource extends Resource
{
    protected static ?string $model = ComplianceStatus::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('framework')->required()->maxLength(120), TextInput::make('control')->required()->maxLength(160), TextInput::make('status')->required()->maxLength(40), TextInput::make('score')->numeric()->minValue(0)->maxValue(100), KeyValue::make('evidence'), DateTimePicker::make('assessed_at'), DateTimePicker::make('expires_at')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('framework')->searchable()->sortable(), TextColumn::make('control')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('score')->numeric(), TextColumn::make('assessed_at')->dateTime(), TextColumn::make('expires_at')->dateTime()])->defaultSort('assessed_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListComplianceStatuses::route('/'), 'create' => CreateComplianceStatus::route('/create'), 'edit' => EditComplianceStatus::route('/{record}/edit')];
    }
}
