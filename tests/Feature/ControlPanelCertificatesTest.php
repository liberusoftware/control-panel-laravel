<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Certificates\Actions\IssueCertificate;
use Liberu\ControlPanel\Certificates\Actions\RevokeCertificate;
use Liberu\ControlPanel\Certificates\CertificatesServiceProvider;
use Liberu\ControlPanel\Certificates\Enums\CertificateStatus;
use Liberu\ControlPanel\Certificates\Events\CertificateIssued;
use Liberu\ControlPanel\Certificates\Events\CertificateRevoked;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(CertificatesServiceProvider::class);
    $this->artisan('migrate');
});

it('issues a certificate and dispatches an after-commit event', function (): void {
    Event::fake();
    $certificate = app(IssueCertificate::class)->execute(['team_id' => 'team-1', 'domains' => ['WWW.Example.com'], 'private_key' => 'secret-key']);

    expect($certificate->status)->toBe(CertificateStatus::Active)->and($certificate->domains)->toBe(['www.example.com'])->and($certificate->private_key)->toBe('secret-key');
    Event::assertDispatched(CertificateIssued::class);
});

it('revokes an active certificate', function (): void {
    $certificate = app(IssueCertificate::class)->execute(['domains' => ['example.com']]);

    Event::fake();
    expect(app(RevokeCertificate::class)->execute($certificate)->status)->toBe(CertificateStatus::Revoked);
    Event::assertDispatched(CertificateRevoked::class);
});

it('rejects empty issuance and duplicate revocation', function (): void {
    expect(fn () => app(IssueCertificate::class)->execute(['domains' => []]))->toThrow(ValidationException::class);
    $certificate = app(IssueCertificate::class)->execute(['domains' => ['example.com']]);
    app(RevokeCertificate::class)->execute($certificate);

    expect(fn () => app(RevokeCertificate::class)->execute($certificate))->toThrow(ValidationException::class);
});
