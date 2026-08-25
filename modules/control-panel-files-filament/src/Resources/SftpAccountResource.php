<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Files\Models\SftpAccount;
use Liberu\ControlPanel\FilesFilament\Resources\SftpAccountResource\Pages\CreateSftpAccount;
use Liberu\ControlPanel\FilesFilament\Resources\SftpAccountResource\Pages\EditSftpAccount;
use Liberu\ControlPanel\FilesFilament\Resources\SftpAccountResource\Pages\ListSftpAccounts;

final class SftpAccountResource extends Resource
{
    protected static ?string $model = SftpAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('owner_id')->required()->maxLength(255),
            TextInput::make('username')->required()->maxLength(120),
            TextInput::make('password')->password()->revealable()->maxLength(255),
            TextInput::make('home_directory')->required()->maxLength(2048),
            TextInput::make('quota_mb')->numeric()->minValue(0),
            TextInput::make('bandwidth_limit_mb')->numeric()->minValue(0),
            Toggle::make('active')->default(true),
            Toggle::make('ssh_key_auth_enabled')->default(false),
            TextInput::make('ssh_public_key')->maxLength(4096),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('username')->searchable(), TextColumn::make('home_directory'), TextColumn::make('quota_mb')->numeric(), TextColumn::make('active')->badge(), TextColumn::make('last_login_at')->dateTime()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListSftpAccounts::route('/'), 'create' => CreateSftpAccount::route('/create'), 'edit' => EditSftpAccount::route('/{record}/edit')];
    }
}
