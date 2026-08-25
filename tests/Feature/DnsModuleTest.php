<?php

declare(strict_types=1);
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Actions\RegisterDnsFeature;
use Liberu\ControlPanel\Dns\DnsServiceProvider;

uses(RefreshDatabase::class);
beforeEach(function (): void {
    app()->register(DnsServiceProvider::class);
    $this->artisan('migrate');
});
it('supports DNS templates, DNSSEC, providers, validation, and propagation checks', function (): void {
    $a = app(RegisterDnsFeature::class);
    $template = $a->execute(['team_id' => 'team-1', 'kind' => 'template', 'name' => 'web', 'records' => [['type' => 'A', 'content' => '192.0.2.1']]]);
    $dnssec = $a->execute(['team_id' => 'team-1', 'kind' => 'dnssec', 'key_tag' => 1, 'algorithm' => 13, 'digest_type' => 2, 'digest' => 'abc']);
    $provider = $a->execute(['team_id' => 'team-1', 'kind' => 'provider', 'name' => 'cloud', 'driver' => 'cloudflare', 'credentials' => ['token' => 'secret']]);
    $validation = $a->execute(['team_id' => 'team-1', 'kind' => 'validation', 'status' => 'passed', 'resolver' => '1.1.1.1', 'expected' => ['A' => '192.0.2.1'], 'observed' => ['A' => '192.0.2.1']]);
    $propagation = $a->execute(['team_id' => 'team-1', 'kind' => 'propagation', 'status' => 'passed', 'nameservers' => ['ns1.example.test'], 'results' => ['ns1.example.test' => true]]);
    expect($template->name)->toBe('web')->and($dnssec->digest)->toBe('abc')->and($provider->credentials)->toMatchArray(['token' => 'secret'])->and($validation->status)->toBe('passed')->and($propagation->status)->toBe('passed');
});
it('rejects unsupported DNS features', function (): void {
    expect(fn () => app(RegisterDnsFeature::class)->execute(['team_id' => 'team-1', 'kind' => 'unknown']))->toThrow(ValidationException::class);
});
