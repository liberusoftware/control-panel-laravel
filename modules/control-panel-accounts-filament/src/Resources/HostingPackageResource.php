<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Accounts\Models\HostingPackage;
use Liberu\ControlPanel\AccountsFilament\Resources\HostingPackageResource\Pages\CreateHostingPackage;
use Liberu\ControlPanel\AccountsFilament\Resources\HostingPackageResource\Pages\EditHostingPackage;

final class HostingPackageResource extends Resource
{
    protected static ?string $model = HostingPackage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(160),
            KeyValue::make('limits')->label('Resource limits'),
            KeyValue::make('features')->label('Feature flags'),
            Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('active')->badge(),
            TextColumn::make('created_at')->dateTime(),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostingPackages::route('/'),
            'create' => CreateHostingPackage::route('/create'),
            'edit' => EditHostingPackage::route('/{record}/edit'),
        ];
    }
}
