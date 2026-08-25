<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\ApiAutomation\Models\ApiCredential;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\ApiCredentialResource\Pages\CreateApiCredential;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\ApiCredentialResource\Pages\EditApiCredential;

final class ApiCredentialResource extends Resource
{
    protected static ?string $model = ApiCredential::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(160),
            KeyValue::make('scopes')->label('Scopes'),
            TextInput::make('secret')->password()->revealable()->maxLength(512),
            Select::make('status')->options(['active' => 'Active', 'revoked' => 'Revoked', 'expired' => 'Expired'])->required()->default('active'),
            DateTimePicker::make('expires_at')->nullable()->native(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('scopes')->listWithLineBreaks(),
            TextColumn::make('status')->badge(),
            TextColumn::make('expires_at')->dateTime()->sortable(),
            TextColumn::make('last_used_at')->dateTime()->sortable(),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListApiCredentials::route('/'), 'create' => CreateApiCredential::route('/create'), 'edit' => EditApiCredential::route('/{record}/edit')];
    }
}
