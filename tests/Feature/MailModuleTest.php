<?php

declare(strict_types=1);
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Actions\ConfigureMailControls;
use Liberu\ControlPanel\Mail\Actions\CreateMailAccount;
use Liberu\ControlPanel\Mail\Actions\CreateMailAlias;
use Liberu\ControlPanel\Mail\Actions\CreateMailRoute;
use Liberu\ControlPanel\Mail\Actions\DeleteMailAccount;
use Liberu\ControlPanel\Mail\Actions\DeleteMailAlias;
use Liberu\ControlPanel\Mail\Actions\DeleteMailRoute;
use Liberu\ControlPanel\Mail\Actions\RecordDeliveryDiagnostic;
use Liberu\ControlPanel\Mail\Actions\RecordMailOperation;
use Liberu\ControlPanel\Mail\Actions\RotateDkimKey;
use Liberu\ControlPanel\Mail\Actions\UpdateMailAccount;
use Liberu\ControlPanel\Mail\Actions\UpdateMailAlias;
use Liberu\ControlPanel\Mail\Actions\UpdateMailRoute;
use Liberu\ControlPanel\Mail\MailServiceProvider;
use Liberu\ControlPanel\Mail\Models\DkimKey;
use Liberu\ControlPanel\Mail\Models\MailAccount;
use Liberu\ControlPanel\Mail\Models\MailControl;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);
beforeEach(function (): void {
    app()->register(MailServiceProvider::class);
    $this->artisan('migrate');
});
it('supports aliases, mailbox controls, spam and virus settings, and diagnostics', function (): void {
    $alias = app(CreateMailAlias::class)->execute(['team_id' => 'team-1', 'domain' => 'example.test', 'address' => 'support', 'destinations' => ['ops@example.test']]);
    $account = app(CreateMailAccount::class)->execute(['team_id' => 'team-1', 'domain' => 'example.test', 'address' => 'support']);
    $controls = app(ConfigureMailControls::class)->execute(['team_id' => 'team-1', 'mail_account_id' => $account->getKey(), 'spam_threshold' => 8, 'virus_scan_enabled' => true, 'autoresponder_enabled' => true]);
    $diagnostic = app(RecordDeliveryDiagnostic::class)->execute(['team_id' => 'team-1', 'mail_account_id' => $account->getKey(), 'recipient' => 'ops@example.test', 'status' => 'delivered']);
    expect($alias->destinations)->toContain('ops@example.test')->and($controls->spam_threshold)->toBe(8)->and($diagnostic->status)->toBe('delivered');
});

it('tracks autoresponder windows and rejects inverted dates', function (): void {
    $account = app(CreateMailAccount::class)->execute(['team_id' => 'team-1', 'domain' => 'example.test', 'address' => 'support']);
    $controls = app(ConfigureMailControls::class)->execute([
        'team_id' => 'team-1', 'mail_account_id' => $account->getKey(), 'autoresponder_enabled' => true,
        'autoresponder_message' => 'Away', 'autoresponder_start_at' => now()->subHour(), 'autoresponder_end_at' => now()->addHour(),
    ]);

    expect($controls->isAutoresponderActive())->toBeTrue()
        ->and(MailControl::query()->whereKey($controls->getKey())->value('autoresponder_message'))->toBe('Away');

    expect(fn () => app(ConfigureMailControls::class)->execute([
        'team_id' => 'team-1', 'mail_account_id' => $account->getKey(), 'autoresponder_start_at' => now()->addDay(), 'autoresponder_end_at' => now(),
    ]))->toThrow(ValidationException::class);
});
it('rejects aliases without destinations and unsafe spam thresholds', function (): void {
    expect(fn () => app(CreateMailAlias::class)->execute(['team_id' => 'team-1', 'domain' => 'example.test', 'address' => 'support', 'destinations' => []]))->toThrow(ValidationException::class);
    expect(fn () => app(ConfigureMailControls::class)->execute(['team_id' => 'team-1', 'mail_account_id' => 'account-1', 'spam_threshold' => 99]))->toThrow(ValidationException::class);
});

it('updates and deletes aliases through domain actions', function (): void {
    $alias = app(CreateMailAlias::class)->execute([
        'team_id' => 'team-1',
        'domain' => 'example.test',
        'address' => 'support',
        'destinations' => ['ops@example.test'],
    ]);

    $updated = app(UpdateMailAlias::class)->execute($alias, [
        'domain' => 'mail.test',
        'address' => 'helpdesk',
        'destinations' => ['team@example.test', 'backup@example.test'],
        'active' => false,
    ]);

    expect($updated->domain)->toBe('mail.test')
        ->and($updated->address)->toBe('helpdesk')
        ->and($updated->destinations)->toBe(['team@example.test', 'backup@example.test'])
        ->and($updated->active)->toBeFalse();

    app(DeleteMailAlias::class)->execute($updated);

    expect($alias->newQuery()->whereKey($alias->getKey())->exists())->toBeFalse();
});

it('rejects alias updates without valid destinations', function (): void {
    $alias = app(CreateMailAlias::class)->execute([
        'team_id' => 'team-1',
        'domain' => 'example.test',
        'address' => 'support',
        'destinations' => ['ops@example.test'],
    ]);

    expect(fn () => app(UpdateMailAlias::class)->execute($alias, ['destinations' => ['not-an-email']]))
        ->toThrow(ValidationException::class);
});

it('updates and deletes mail routes through domain actions', function (): void {
    $route = app(CreateMailRoute::class)->execute([
        'team_id' => 'team-1',
        'domain' => 'example.test',
        'source_pattern' => 'support',
        'destination' => 'ops@example.test',
    ]);

    $updated = app(UpdateMailRoute::class)->execute($route, [
        'domain' => 'mail.test',
        'source_pattern' => 'helpdesk',
        'destination' => 'team@example.test',
        'priority' => 20,
        'active' => false,
    ]);

    expect($updated->domain)->toBe('mail.test')
        ->and($updated->source_pattern)->toBe('helpdesk')
        ->and($updated->destination)->toBe('team@example.test')
        ->and($updated->priority)->toBe(20)
        ->and($updated->active)->toBeFalse();

    app(DeleteMailRoute::class)->execute($updated);

    expect($route->newQuery()->whereKey($route->getKey())->exists())->toBeFalse();
});

it('rotates DKIM keys without writing provider-specific mail configuration', function (): void {
    $first = app(RotateDkimKey::class)->execute('team-1', 'example.test', 'default');
    $second = app(RotateDkimKey::class)->execute('team-1', 'example.test', 'default');

    expect($first->fresh()->active)->toBeFalse()
        ->and($second->active)->toBeTrue()
        ->and($second->private_key)->toContain('BEGIN PRIVATE KEY')
        ->and(DkimKey::query()->where('team_id', 'team-2')->count())->toBe(0);
});

it('updates mailbox settings while preserving lifecycle state', function (): void {
    $account = app(CreateMailAccount::class)->execute(['team_id' => 'team-1', 'domain' => 'example.test', 'address' => 'support', 'quota_bytes' => 100]);

    $updated = app(UpdateMailAccount::class)->execute($account, ['domain' => 'mail.test', 'address' => 'helpdesk@mail.test', 'quota_bytes' => 200]);

    expect($updated->domain)->toBe('mail.test')->and($updated->address)->toBe('helpdesk@mail.test')->and($updated->quota_bytes)->toBe(200)->and($updated->status)->toBe('active');
});

it('deletes a mail account through the domain action', function (): void {
    $account = app(CreateMailAccount::class)->execute(['team_id' => 'team-1', 'domain' => 'example.test', 'address' => 'support']);

    app(DeleteMailAccount::class)->execute($account);

    expect(MailAccount::query()->whereKey($account->getKey())->exists())->toBeFalse();
});

it('scopes mail operation and diagnostic references to their tenant', function (): void {
    $account = app(CreateMailAccount::class)->execute(['team_id' => 'team-1', 'domain' => 'example.test', 'address' => 'support']);

    expect(fn () => app(RecordMailOperation::class)->execute(['team_id' => 'team-2', 'mail_account_id' => $account->getKey(), 'operation' => 'deliver']))
        ->toThrow(HttpException::class);
    expect(fn () => app(RecordDeliveryDiagnostic::class)->execute(['team_id' => 'team-2', 'mail_account_id' => $account->getKey(), 'recipient' => 'ops@example.test']))
        ->toThrow(HttpException::class);
});
