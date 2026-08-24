<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Mail\Models\MailAccount;
use Liberu\ControlPanel\MailFilament\Resources\MailAccountResource\Pages\ListMailAccounts;

final class MailAccountResource extends Resource
{
    protected static ?string $model = MailAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('address')->searchable()->sortable(), TextColumn::make('domain')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('quota_bytes')->numeric(), TextColumn::make('created_at')->dateTime()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListMailAccounts::route('/')];
    }
}
