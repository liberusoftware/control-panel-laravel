<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\ControlPanel\Certificates\Actions\ExpireCertificate;
use Liberu\ControlPanel\Certificates\Actions\IssueCertificate;
use Liberu\ControlPanel\Certificates\CertificatesServiceProvider;
use Liberu\ControlPanel\CertificatesLivewire\CertificatesLivewireServiceProvider;
use Liberu\ControlPanel\CertificatesLivewire\Components\CertificateOperationInventory;
use Liberu\Foundation\Organizations\Models\Team;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(CertificatesServiceProvider::class);
    app()->register(CertificatesLivewireServiceProvider::class);
    $this->artisan('migrate');
});

it('expires only a current-team certificate from Livewire', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $certificate = app(IssueCertificate::class)->execute([
        'team_id' => $team->getKey(), 'domains' => ['expired.example.test'], 'expires_at' => now()->subMinute(),
    ]);

    $this->actingAs($user);
    app(CertificateOperationInventory::class)->expire($certificate->getKey(), app(ExpireCertificate::class));

    expect($certificate->refresh()->status->value)->toBe('expired');
});
