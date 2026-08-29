<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Mail\Actions\DeleteMailRoute;
use Liberu\ControlPanel\Mail\Models\MailRoute;
use Liberu\ControlPanel\MailFilament\Resources\MailRouteResource\Pages\CreateMailRoute;
use Liberu\ControlPanel\MailFilament\Resources\MailRouteResource\Pages\EditMailRoute;
use Liberu\ControlPanel\MailFilament\Resources\MailRouteResource\Pages\ListMailRoutes;

final class MailRouteResource extends Resource
{
    protected static ?string $model = MailRoute::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-right-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('domain')->required()->maxLength(253),
            TextInput::make('source_pattern')->required()->maxLength(255),
            TextInput::make('destination')->required()->email()->maxLength(320),
            TextInput::make('priority')->numeric()->minValue(0)->default(100),
            Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('domain')->searchable(), TextColumn::make('source_pattern'), TextColumn::make('destination')->searchable(), TextColumn::make('priority')->sortable(), TextColumn::make('active')->badge()])->recordActions([
            DeleteAction::make()->action(function (MailRoute $record): void {
                abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
                abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);
                app(DeleteMailRoute::class)->execute($record);
            }),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListMailRoutes::route('/'), 'create' => CreateMailRoute::route('/create'), 'edit' => EditMailRoute::route('/{record}/edit')];
    }
}
