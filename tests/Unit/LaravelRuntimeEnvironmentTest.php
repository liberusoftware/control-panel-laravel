<?php

use App\Hosting\Kubernetes\LaravelRuntimeEnvironment;
use App\Hosting\Kubernetes\TenantWorkloadPlan;

beforeEach(function (): void {
    $this->environmentFile = tempnam(sys_get_temp_dir(), 'hosting-runtime-');
    chmod($this->environmentFile, 0600);
    file_put_contents($this->environmentFile, 'STRIPE_SECRET="sk_test_private"'."\nAPP_DEBUG=true\nDB_HOST=old-host\n");
    $this->runtimeSettings = [
        'environment_file' => $this->environmentFile, 'app_key' => 'base64:'.base64_encode(str_repeat('a', 32)),
        'app_url' => 'https://site.example.test',
        'database' => ['connection' => 'pgsql', 'host' => 'app-rw.database.svc', 'database' => 'website', 'username' => 'website', 'password' => 'private-db-password'],
    ];
});

afterEach(function (): void {
    @unlink($this->environmentFile);
});

it('imports application secrets while forcing production site and database settings', function (): void {
    $result = (new LaravelRuntimeEnvironment())->build($this->runtimeSettings);
    expect($result['values'])->toMatchArray([
        'STRIPE_SECRET' => 'sk_test_private', 'APP_DEBUG' => 'false', 'APP_ENV' => 'production',
        'APP_URL' => 'https://site.example.test', 'DB_HOST' => 'app-rw.database.svc', 'DB_PORT' => '5432',
        'DB_PASSWORD' => 'private-db-password', 'DB_SSLMODE' => 'verify-full', 'DB_URL' => '',
    ])->and($result['contents'])->toContain('STRIPE_SECRET');
});

it('rejects exposed production environment files', function (): void {
    chmod($this->environmentFile, 0644);
    expect(fn () => (new LaravelRuntimeEnvironment())->build($this->runtimeSettings))->toThrow(InvalidArgumentException::class, 'private');
});

it('does not print invalid dotenv content in errors', function (): void {
    file_put_contents($this->environmentFile, 'STRIPE_SECRET="private-unclosed');
    try {
        (new LaravelRuntimeEnvironment())->build($this->runtimeSettings);
        $this->fail('Invalid dotenv should be rejected.');
    } catch (InvalidArgumentException $error) {
        expect($error->getMessage())->not->toContain('private-unclosed')->toContain('invalid dotenv syntax');
    }
});

it('rejects infrastructure credentials rather than forwarding them to tenants', function (string $key): void {
    file_put_contents($this->environmentFile, $key.'=private-token');
    expect(fn () => (new LaravelRuntimeEnvironment())->build($this->runtimeSettings))->toThrow(InvalidArgumentException::class, 'credentials');
})->with(['GITHUB_TOKEN', 'GITLAB_TOKEN', 'GH_TOKEN', 'CI_JOB_TOKEN', 'ACTIONS_RUNTIME_TOKEN', 'KUBECONFIG', 'HOSTING_KUBERNETES_TOKEN', 'DB_ROOT_PASSWORD']);

it('rejects site URLs containing credentials', function (): void {
    $this->runtimeSettings['app_url'] = 'https://private-user@site.example.test';
    expect(fn () => (new LaravelRuntimeEnvironment())->build($this->runtimeSettings))->toThrow(InvalidArgumentException::class, 'HTTPS site URL');
});

it('creates private immutable environment and stable key secrets with read-only mounts', function (): void {
    $image = 'registry.example/site@sha256:'.str_repeat('a', 64);
    $settings = ['allowed_images' => [$image], 'runtime_class' => 'gvisor', 'network_policy_enforced' => true,
        'storage_gi' => 2, 'storage_class' => 'durable', 'laravel' => $this->runtimeSettings];
    $plan = (new TenantWorkloadPlan())->build('team', 'workload', $image, $settings);
    $secrets = collect($plan)->where('body.kind', 'Secret')->pluck('body');
    expect($secrets)->toHaveCount(2)->and($secrets->every(fn (array $secret): bool => $secret['immutable']))->toBeTrue();
    $pod = collect($plan)->firstWhere('body.kind', 'Deployment')['body']['spec']['template']['spec'];
    expect(collect($pod['containers'][0]['volumeMounts'])->firstWhere('name', 'environment'))->toMatchArray(['readOnly' => true, 'mountPath' => '/var/www/html/.env']);
    expect(json_encode($pod))->not->toContain('sk_test_private', 'private-db-password');
    $keyVariable = collect($pod['containers'][0]['env'])->firstWhere('name', 'APP_KEY');
    expect($keyVariable['valueFrom']['secretKeyRef']['name'])->toBe('app-key');
});
