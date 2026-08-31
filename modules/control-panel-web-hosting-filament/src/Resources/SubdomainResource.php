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
use Liberu\ControlPanel\WebHosting\Models\Subdomain;
use Liberu\ControlPanel\WebHostingFilament\Resources\SubdomainResource\Pages\CreateSubdomain;
use Liberu\ControlPanel\WebHostingFilament\Resources\SubdomainResource\Pages\EditSubdomain;
use Liberu\ControlPanel\WebHostingFilament\Resources\SubdomainResource\Pages\ListSubdomains;

final class SubdomainResource extends Resource
{
    protected static ?string $model = Subdomain::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|\UnitEnum|null $navigationGroup = 'Web Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('domain_id')->required()->uuid(),
            TextInput::make('subdomain')->required()->maxLength(253),
            TextInput::make('document_root')->required()->startsWith('/')->maxLength(2048),
            TextInput::make('php_version')->maxLength(40),
            Toggle::make('active')->default(true),
            TextInput::make('redirect_url')->url()->maxLength(2048),
            TextInput::make('redirect_type')->numeric()->in([301, 302]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('full_name')->label('Hostname')->state(fn (Subdomain $record): string => $record->full_name), TextColumn::make('document_root'), TextColumn::make('php_version'), TextColumn::make('active')->badge(), TextColumn::make('redirect_type')->badge()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('domain', fn (Builder $query) => $query->where('team_id', auth()->user()?->current_team_id));
    }

    public static function getPages(): array
    {
        return ['index' => ListSubdomains::route('/'), 'create' => CreateSubdomain::route('/create'), 'edit' => EditSubdomain::route('/{record}/edit')];
    }
}
