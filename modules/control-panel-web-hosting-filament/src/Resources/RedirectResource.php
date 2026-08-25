<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\WebHostingFilament\Resources;
use Filament\Resources\Resource; use Filament\Schemas\Schema; use Filament\Tables\Columns\TextColumn; use Filament\Tables\Table; use Illuminate\Database\Eloquent\Builder; use Liberu\ControlPanel\WebHosting\Models\Redirect; use Liberu\ControlPanel\WebHostingFilament\Resources\RedirectResource\Pages\ListRedirects;
final class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class; protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-right'; protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';
    public static function form(Schema $schema): Schema { return $schema->components([]); }
    public static function table(Table $table): Table { return $table->columns([TextColumn::make('domain_id'), TextColumn::make('source')->searchable(), TextColumn::make('destination'), TextColumn::make('status_code')->badge(), TextColumn::make('active')->badge()])->defaultSort('created_at', 'desc'); }
    public static function getEloquentQuery(): Builder { return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id); }
    public static function getPages(): array { return ['index' => ListRedirects::route('/')]; }
}
