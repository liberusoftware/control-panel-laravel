<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\WebHostingFilament\Resources;
use Filament\Resources\Resource; use Filament\Schemas\Schema; use Filament\Tables\Columns\TextColumn; use Filament\Tables\Table; use Illuminate\Database\Eloquent\Builder; use Liberu\ControlPanel\WebHosting\Models\HostedApplication; use Liberu\ControlPanel\WebHostingFilament\Resources\HostedApplicationResource\Pages\ListHostedApplications;
final class HostedApplicationResource extends Resource
{
    protected static ?string $model = HostedApplication::class; protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-window'; protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';
    public static function form(Schema $schema): Schema { return $schema->components([]); }
    public static function table(Table $table): Table { return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('type')->badge(), TextColumn::make('version'), TextColumn::make('document_root'), TextColumn::make('status')->badge()])->defaultSort('created_at', 'desc'); }
    public static function getEloquentQuery(): Builder { return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id); }
    public static function getPages(): array { return ['index' => ListHostedApplications::route('/')]; }
}
