<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\WebHostingFilament\Resources;
use Filament\Resources\Resource; use Filament\Schemas\Schema; use Filament\Tables\Columns\TextColumn; use Filament\Tables\Table; use Illuminate\Database\Eloquent\Builder; use Liberu\ControlPanel\WebHosting\Models\VirtualHost; use Liberu\ControlPanel\WebHostingFilament\Resources\VirtualHostResource\Pages\ListVirtualHosts;
final class VirtualHostResource extends Resource
{
    protected static ?string $model = VirtualHost::class; protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt'; protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';
    public static function form(Schema $schema): Schema { return $schema->components([]); }
    public static function table(Table $table): Table { return $table->columns([TextColumn::make('domain.hostname')->searchable(), TextColumn::make('server')->badge(), TextColumn::make('runtime'), TextColumn::make('document_root'), TextColumn::make('active')->badge()])->defaultSort('created_at', 'desc'); }
    public static function getEloquentQuery(): Builder { return parent::getEloquentQuery()->whereHas('domain', fn (Builder $query) => $query->where('team_id', auth()->user()?->current_team_id)); }
    public static function getPages(): array { return ['index' => ListVirtualHosts::route('/')]; }
}
