<?php

declare(strict_types=1);
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Actions\ConfigureMailControls;
use Liberu\ControlPanel\Mail\Actions\CreateMailAlias;
use Liberu\ControlPanel\Mail\Actions\RecordDeliveryDiagnostic;
use Liberu\ControlPanel\Mail\Actions\RotateDkimKey;
use Liberu\ControlPanel\Mail\MailServiceProvider;
use Liberu\ControlPanel\Mail\Models\DkimKey;

uses(RefreshDatabase::class);
beforeEach(function (): void {
    app()->register(MailServiceProvider::class);
    $this->artisan('migrate');
});
it('supports aliases, mailbox controls, spam and virus settings, and diagnostics', function (): void {
    $alias = app(CreateMailAlias::class)->execute(['team_id' => 'team-1', 'domain' => 'example.test', 'address' => 'support', 'destinations' => ['ops@example.test']]);
    $controls = app(ConfigureMailControls::class)->execute(['team_id' => 'team-1', 'mail_account_id' => 'account-1', 'spam_threshold' => 8, 'virus_scan_enabled' => true, 'autoresponder_enabled' => true]);
    $diagnostic = app(RecordDeliveryDiagnostic::class)->execute(['team_id' => 'team-1', 'mail_account_id' => 'account-1', 'recipient' => 'ops@example.test', 'status' => 'delivered']);
    expect($alias->destinations)->toContain('ops@example.test')->and($controls->spam_threshold)->toBe(8)->and($diagnostic->status)->toBe('delivered');
});
it('rejects aliases without destinations and unsafe spam thresholds', function (): void {
    expect(fn () => app(CreateMailAlias::class)->execute(['team_id' => 'team-1', 'domain' => 'example.test', 'address' => 'support', 'destinations' => []]))->toThrow(ValidationException::class);
    expect(fn () => app(ConfigureMailControls::class)->execute(['team_id' => 'team-1', 'mail_account_id' => 'account-1', 'spam_threshold' => 99]))->toThrow(ValidationException::class);
});

it('rotates DKIM keys without writing provider-specific mail configuration', function (): void {
    $first = app(RotateDkimKey::class)->execute('team-1', 'example.test', 'default');
    $second = app(RotateDkimKey::class)->execute('team-1', 'example.test', 'default');

    expect($first->fresh()->active)->toBeFalse()
        ->and($second->active)->toBeTrue()
        ->and($second->private_key)->toContain('BEGIN PRIVATE KEY')
        ->and(DkimKey::query()->where('team_id', 'team-2')->count())->toBe(0);
});
