<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Mail\Models\DkimKey;
use Liberu\ControlPanel\MailFilament\Resources\DkimKeyResource\Pages\CreateDkimKey;
use Liberu\ControlPanel\MailFilament\Resources\DkimKeyResource\Pages\ListDkimKeys;

final class DkimKeyResource extends Resource
{
    protected static ?string $model = DkimKey::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Email & Messaging';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('domain')->required()->maxLength(253),
            TextInput::make('selector')->default('default')->required()->maxLength(63),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('domain')->searchable()->sortable(),
            TextColumn::make('selector'),
            TextColumn::make('active')->badge(),
            TextColumn::make('rotated_at')->dateTime()->sortable(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListDkimKeys::route('/'), 'create' => CreateDkimKey::route('/create')];
    }
}
