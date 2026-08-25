<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Accounts\Models\Account;
use Liberu\ControlPanel\AccountsFilament\Resources\AccountResource\Pages\ListAccounts;

final class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('type')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('owner_id')->label('Owner')->searchable(),
            TextColumn::make('quota_overrides')->label('Quota limits')->formatStateUsing(static fn (?array $state): string => (string) count($state ?? [])),
            TextColumn::make('brand')->label('Branding')->formatStateUsing(static fn (?array $state): string => (string) count($state ?? [])),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListAccounts::route('/')];
    }
}
