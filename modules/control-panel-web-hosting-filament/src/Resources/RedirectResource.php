<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\WebHosting\Models\Redirect;
use Liberu\ControlPanel\WebHostingFilament\Resources\RedirectResource\Pages\CreateRedirect;
use Liberu\ControlPanel\WebHostingFilament\Resources\RedirectResource\Pages\EditRedirect;
use Liberu\ControlPanel\WebHostingFilament\Resources\RedirectResource\Pages\ListRedirects;

final class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Web Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('domain_id')->required()->uuid(),
            TextInput::make('source')->required()->maxLength(1024),
            TextInput::make('destination')->required()->url()->maxLength(2048),
            TextInput::make('status_code')->numeric()->in([301, 302, 307, 308])->default(301),
            Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('domain_id'), TextColumn::make('source')->searchable(), TextColumn::make('destination'), TextColumn::make('status_code')->badge(), TextColumn::make('active')->badge()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListRedirects::route('/'), 'create' => CreateRedirect::route('/create'), 'edit' => EditRedirect::route('/{record}/edit')];
    }
}
