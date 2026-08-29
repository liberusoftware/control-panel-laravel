<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreFilament\Resources\NodeCredentialResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ControlCore\Actions\GenerateSshKeyPair;
use Liberu\ControlPanel\ControlCoreFilament\Resources\NodeCredentialResource;

final class ListNodeCredentials extends ListRecords
{
    protected static string $resource = NodeCredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('generateKeyPair')
            ->label('Generate SSH key pair')
            ->form([
                TextInput::make('passphrase')->password()->minLength(8),
                Select::make('bits')->options([2048 => '2048', 4096 => '4096'])->default(4096)->required(),
                TextInput::make('comment')->maxLength(255),
            ])
            ->action(function (array $data): void {
                $pair = app(GenerateSshKeyPair::class)->execute($data['passphrase'] ?? null, (int) $data['bits'], $data['comment'] ?? null);
                Notification::make()->title('SSH key pair generated')->body("Public key:\n{$pair['public_key']}\n\nPrivate key:\n{$pair['private_key']}")->persistent()->success()->send();
            })];
    }
}
