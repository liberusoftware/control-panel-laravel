<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Mail\Actions\DeleteMailAlias;
use Liberu\ControlPanel\Mail\Models\MailAlias;
use Liberu\ControlPanel\MailFilament\Resources\MailAliasResource\Pages\CreateMailAlias;
use Liberu\ControlPanel\MailFilament\Resources\MailAliasResource\Pages\EditMailAlias;
use Liberu\ControlPanel\MailFilament\Resources\MailAliasResource\Pages\ListMailAliases;

final class MailAliasResource extends Resource
{
    protected static ?string $model = MailAlias::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|\UnitEnum|null $navigationGroup = 'Email & Messaging';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('domain')->required()->maxLength(253),
            TextInput::make('address')->required()->email()->maxLength(320),
            TagsInput::make('destinations')->required()->nestedRecursiveRules(['email']),
            Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('domain'), TextColumn::make('address')->searchable(), TextColumn::make('destinations')->listWithLineBreaks(), TextColumn::make('active')->badge()])->recordActions([
            DeleteAction::make()->action(function (MailAlias $record): void {
                abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
                abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);
                app(DeleteMailAlias::class)->execute($record);
            }),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListMailAliases::route('/'), 'create' => CreateMailAlias::route('/create'), 'edit' => EditMailAlias::route('/{record}/edit')];
    }
}
