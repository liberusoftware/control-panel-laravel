<?php

use Symfony\Component\Process\Process;

beforeEach(function (): void {
    $this->projectRoot = dirname(__DIR__);
    $this->sandbox = sys_get_temp_dir().'/panel-deployment-'.bin2hex(random_bytes(8));
    mkdir($this->sandbox.'/bin', 0700, true);
    $this->sandbox = realpath($this->sandbox);
    $this->stubCommand = function (string $name, string $body): void {
        $path = $this->sandbox.'/bin/'.$name;
        file_put_contents($path, "#!/bin/sh\nset -eu\n".$body."\n");
        chmod($path, 0700);
    };
    $this->runScript = function (string $script, array $arguments = [], array $environment = []): Process {
        $process = new Process(['bash', $this->projectRoot.'/'.$script, ...$arguments], $this->sandbox, [
            'PATH' => $this->sandbox.'/bin:'.getenv('PATH'),
            'CONTAINER_MODE' => false,
            'PANEL_SERVICES' => false, 'CI' => false, 'GITHUB_ACTIONS' => false, 'KUBE_CONTEXT' => false, 'APP_ENV_FILE' => false,
            ...$environment,
        ]);
        $process->run();

        return $process;
    };
});

afterEach(function (): void {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->sandbox, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($this->sandbox);
});

it('offers installer help from a different working directory without requiring services', function (): void {
    $result = ($this->runScript)('install.sh', ['--help']);

    expect($result->isSuccessful())->toBeTrue()
        ->and($result->getOutput())->toContain('standalone', 'compose', 'kubernetes');
});

it('rejects unknown and unattended missing deployment modes', function (array $arguments): void {
    expect(($this->runScript)('install.sh', $arguments)->getExitCode())->toBe(2);
})->with([[[]], [['unsupported']]]);

it('initializes fresh mounted runtime storage before executing an explicit command', function (): void {
    $result = ($this->runScript)('.docker/start-container', ['sh', '-c', 'exit 17']);

    expect($result->getExitCode())->toBe(17);
    foreach (['storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $directory) {
        expect(is_dir($this->sandbox.'/'.$directory))->toBeTrue();
    }
});

it('checks the local service for each container mode', function (string $mode, string $command, string $expected): void {
    ($this->stubCommand)($command, 'printf "%s\\n" "$*"');
    $result = ($this->runScript)('.docker/healthcheck', [], ['CONTAINER_MODE' => $mode]);

    expect($result->isSuccessful())->toBeTrue()
        ->and($result->getOutput())->toContain($expected);
})->with([
    ['http', 'curl', 'http://127.0.0.1:8000/up'],
    ['horizon', 'supervisorctl', 'horizon:horizon_0'],
    ['scheduler', 'supervisorctl', 'scheduler:scheduler_0'],
    ['worker', 'supervisorctl', 'worker:worker_0'],
    ['reverb', 'php', 'REVERB_SERVER_PORT'],
]);

it('fails the health check when the local service fails', function (string $mode, string $command): void {
    ($this->stubCommand)($command, 'exit 3');

    expect(($this->runScript)('.docker/healthcheck', [], ['CONTAINER_MODE' => $mode])->getExitCode())->toBe(3);
})->with([
    ['http', 'curl'], ['horizon', 'supervisorctl'], ['worker', 'supervisorctl'], ['scheduler', 'supervisorctl'], ['reverb', 'php'],
]);

it('rejects an unsupported health check mode', function (): void {
    expect(($this->runScript)('.docker/healthcheck', [], ['CONTAINER_MODE' => 'invalid'])->isSuccessful())->toBeFalse();
});

it('rejects a missing JavaScript lockfile before changing the standalone installation', function (): void {
    mkdir($this->sandbox.'/scripts');
    copy($this->projectRoot.'/scripts/setup.sh', $this->sandbox.'/scripts/setup.sh');
    file_put_contents($this->sandbox.'/composer.json', '{}');
    file_put_contents($this->sandbox.'/package.json', '{"packageManager":"npm@11.0.0"}');
    file_put_contents($this->sandbox.'/.env.example', 'APP_KEY=');
    ($this->stubCommand)('composer', 'touch composer-was-run');
    ($this->stubCommand)('npm', 'exit 0');
    $process = new Process(['bash', 'scripts/setup.sh'], $this->sandbox, ['PATH' => $this->sandbox.'/bin:'.getenv('PATH')]);
    $process->run();

    expect($process->isSuccessful())->toBeFalse()
        ->and($process->getErrorOutput())->toContain('package-lock.json is required')
        ->and(file_exists($this->sandbox.'/.env'))->toBeFalse()
        ->and(file_exists($this->sandbox.'/composer-was-run'))->toBeFalse();
});

it('offers Kubernetes configuration help without credentials or cluster tools', function (): void {
    $result = ($this->runScript)('install.sh', ['kubernetes', '--help'], ['APP_KEY' => false, 'DOMAIN' => false]);

    expect($result->isSuccessful())->toBeTrue()
        ->and($result->getOutput())->toContain('IMAGE_TAG', 'ReadWriteMany');
});

it('rejects malformed Kubernetes hostnames before touching the cluster', function (string $hostname): void {
    ($this->stubCommand)('kubectl', 'touch cluster-was-touched');
    $result = ($this->runScript)('k8s/deploy.sh', [], [
        'DOMAIN' => $hostname, 'APP_KEY' => 'private-app-key',
        'DB_PASSWORD' => 'private-db-password', 'DB_ROOT_PASSWORD' => 'private-root-password',
        'IMAGE_TAG' => 'release-1',
    ]);

    expect($result->getExitCode())->toBe(2)
        ->and(file_exists($this->sandbox.'/cluster-was-touched'))->toBeFalse();
})->with(['panel..example.com', 'panel.-example.com', str_repeat('a', 64).'.example.com']);

it('propagates Kubernetes rollout failures and keeps credentials out of command arguments', function (): void {
    ($this->stubCommand)('kubectl', <<<'SH'
        printf '%s\n' "$*" >> "$COMMAND_LOG"
        case "$1" in
            kustomize) cat "$2/kustomization.yaml" ;;
            create) printf '{}\n' ;;
            apply) if [ "$3" = '-' ]; then cat > /dev/null; fi ;;
            rollout) exit 9 ;;
        esac
        SH);
    $result = ($this->runScript)('k8s/deploy.sh', [], [
        'COMMAND_LOG' => $this->sandbox.'/commands.log',
        'DOMAIN' => 'panel.example.com', 'APP_KEY' => 'private-app-key',
        'DB_PASSWORD' => 'private-db-password', 'DB_ROOT_PASSWORD' => 'private-root-password',
        'IMAGE_TAG' => 'release-1',
    ]);
    $commands = file_get_contents($this->sandbox.'/commands.log');

    expect($result->getExitCode())->toBe(9)
        ->and($result->getOutput())->not->toContain('Deployment ready')
        ->and($commands)->not->toContain('private-app-key', 'private-db-password', 'private-root-password');
    preg_match('/--from-env-file=([^\s]+)/', $commands, $matches);
    expect($matches)->toHaveCount(2)
        ->and(file_exists($matches[1]))->toBeFalse();
});

it('does not start services when another container holds the migration lock', function (): void {
    ($this->stubCommand)('php', <<<'SH'
        printf '%s\n' "$*"
        exit 75
        SH);
    $result = ($this->runScript)('.docker/start-container', [], [
        'CONTAINER_MODE' => 'http', 'RUNNING_MIGRATIONS_AND_SEEDERS' => 'true',
    ]);

    expect($result->getExitCode())->toBe(75)
        ->and($result->getOutput())->toContain('migrate --isolated=75 --force --no-interaction')
        ->and($result->getOutput())->not->toContain('Octane Server:', '--seed');
});

function panelDeploymentEnvironment(object $test): array
{
    return ['DOMAIN' => 'panel.example.com', 'APP_KEY' => 'private-app-key',
        'DB_PASSWORD' => 'private-db-password', 'DB_ROOT_PASSWORD' => 'private-root-password',
        'IMAGE_TAG' => 'release-1', 'COMMAND_LOG' => $test->sandbox.'/commands.log'];
}

function stubPanelDeploymentKubectl(object $test): void
{
    ($test->stubCommand)('kubectl', <<<'SH'
        printf '%s\n' "$*" >> "$COMMAND_LOG"
        case "$1" in
            kustomize) cat "$2/kustomization.yaml" ;;
            get) case "$2" in
                certificate) cat "$CERTIFICATES_FILE" ;;
                ingress) cat "$INGRESSES_FILE" ;;
                *) printf '{}\n' ;;
                esac ;;
            create) printf '{}\n' ;;
            apply) if [ "$3" = '-' ]; then cat > /dev/null; fi ;;
            wait) if [ "${FAIL_MIGRATION:-false}" = true ] && [ "$2" = '--for=condition=complete' ]; then exit 31; fi ;;
        esac
        SH);
}

it('blocks application rollout when the release migration job fails', function (): void {
    stubPanelDeploymentKubectl($this);
    $result = ($this->runScript)('k8s/deploy.sh', [], [...panelDeploymentEnvironment($this), 'FAIL_MIGRATION' => 'true']);
    $commands = file_get_contents($this->sandbox.'/commands.log');
    expect($result->getExitCode())->toBe(31)
        ->and($commands)->toContain('component=migration')
        ->not->toContain('component in (application,horizon,reverb,scheduler)')
        ->and($result->getOutput())->not->toContain('Deployment ready');
});

it('requires an explicit Kubernetes context in CI before contacting a cluster', function (): void {
    ($this->stubCommand)('kubectl', 'touch cluster-was-touched');
    $result = ($this->runScript)('k8s/deploy.sh', [], [...panelDeploymentEnvironment($this), 'CI' => 'true']);
    expect($result->getExitCode())->toBe(2)->and(file_exists($this->sandbox.'/cluster-was-touched'))->toBeFalse();
});

it('refuses an exposed production dotenv file before changing the cluster', function (): void {
    ($this->stubCommand)('kubectl', <<<'SH'
        if [ "$1" = get ]; then printf '{}\n'; else touch cluster-was-mutated; fi
        SH);
    $path = $this->sandbox.'/production.env';
    file_put_contents($path, 'STRIPE_SECRET=private-stripe');
    chmod($path, 0644);
    $result = ($this->runScript)('k8s/deploy.sh', [], [...panelDeploymentEnvironment($this), 'APP_ENV_FILE' => $path]);
    expect($result->isSuccessful())->toBeFalse()
        ->and(file_exists($this->sandbox.'/cluster-was-mutated'))->toBeFalse()
        ->and($result->getErrorOutput())->not->toContain('private-stripe');
});

it('only reports successful deployment after verifying current certificates and ingress addresses', function (bool $expired): void {
    stubPanelDeploymentKubectl($this);
    $certificates = [];
    $ingresses = [];
    foreach (['boilerplate-tls' => ['boilerplate-laravel', 'panel.example.com'], 'boilerplate-reverb-tls' => ['boilerplate-reverb', 'ws.panel.example.com']] as $name => [$route, $host]) {
        $certificates[] = ['metadata' => ['name' => $name, 'generation' => 2],
            'spec' => ['dnsNames' => [$host], 'issuerRef' => ['name' => 'letsencrypt-prod']],
            'status' => ['notAfter' => gmdate(DATE_ATOM, time() + ($expired ? -86400 : 86400)), 'conditions' => [['type' => 'Ready', 'status' => 'True', 'observedGeneration' => 2]]]];
        $ingresses[] = ['metadata' => ['name' => $route], 'spec' => ['rules' => [['host' => $host]]], 'status' => ['loadBalancer' => ['ingress' => [['ip' => '192.0.2.10']]]]];
    }
    file_put_contents($this->sandbox.'/certificates.json', json_encode(['items' => $certificates], JSON_THROW_ON_ERROR));
    file_put_contents($this->sandbox.'/ingresses.json', json_encode(['items' => $ingresses], JSON_THROW_ON_ERROR));
    $result = ($this->runScript)('k8s/deploy.sh', [], [...panelDeploymentEnvironment($this),
        'CERTIFICATES_FILE' => $this->sandbox.'/certificates.json', 'INGRESSES_FILE' => $this->sandbox.'/ingresses.json']);
    expect($result->isSuccessful())->toBe(! $expired);
    if ($expired) {
        expect($result->getOutput())->not->toContain('Deployment ready');
    } else {
        expect($result->getOutput())->toContain('Deployment ready');
    }
})->with([false, true]);

it('keeps live validation read-only and fails when its cluster is unavailable', function (): void {
    ($this->stubCommand)('kubectl', <<<'SH'
        case "$1" in kustomize) exit 0 ;; cluster-info) exit 1 ;; *) touch cluster-was-mutated ;; esac
        SH);
    $result = ($this->runScript)('k8s/validate.sh');
    expect($result->isSuccessful())->toBeFalse()->and(file_exists($this->sandbox.'/cluster-was-mutated'))->toBeFalse();
});

function externalPanelDeploymentEnvironment(object $test): array
{
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $request = openssl_csr_new(['commonName' => 'service-test'], $key, ['digest_alg' => 'sha256']);
    $certificate = openssl_csr_sign($request, null, $key, 1, ['digest_alg' => 'sha256']);
    $ca = $test->sandbox.'/service-ca.pem';
    openssl_x509_export_to_file($certificate, $ca);
    chmod($ca, 0600);

    return [...panelDeploymentEnvironment($test), 'PANEL_SERVICES' => 'external', 'DB_ROOT_PASSWORD' => false,
        'DB_HOST' => 'database.example.test', 'DB_DATABASE' => 'panel', 'DB_USERNAME' => 'panel', 'DB_PASSWORD' => str_repeat('d', 40),
        'DB_CA_FILE' => $ca, 'DB_NAMESPACE' => 'database-system', 'REDIS_HOST' => 'queue.example.test',
        'REDIS_PASSWORD' => str_repeat('r', 40), 'REDIS_CA_FILE' => $ca, 'REDIS_NAMESPACE' => 'queue-system', 'REDIS_TOPOLOGY' => 'replicated'];
}

it('deploys the external profile without bundled root credentials or bundled service rollouts and still gates migrations', function (): void {
    stubPanelDeploymentKubectl($this);
    $environment = externalPanelDeploymentEnvironment($this);
    $result = ($this->runScript)('k8s/deploy.sh', [], [...$environment, 'FAIL_MIGRATION' => 'true']);
    $commands = file_get_contents($this->sandbox.'/commands.log');
    expect($result->getErrorOutput())->toBe('');
    expect($result->getExitCode())->toBe(31)
        ->and($commands)->toContain('create secret generic boilerplate-service-ca', 'component=migration')
        ->not->toContain('create secret generic boilerplate-database-admin', 'rollout status statefulset/boilerplate-mysql',
            'rollout status deployment/boilerplate-redis', str_repeat('d', 40), str_repeat('r', 40), 'component in (application,horizon,reverb,scheduler)');
});

it('rejects a sharded Horizon endpoint before mutating any Kubernetes resources', function (): void {
    stubPanelDeploymentKubectl($this);
    $result = ($this->runScript)('k8s/deploy.sh', [], [...externalPanelDeploymentEnvironment($this), 'REDIS_TOPOLOGY' => 'cluster']);
    $commands = file_get_contents($this->sandbox.'/commands.log');
    expect($result->isSuccessful())->toBeFalse()->and($commands)->not->toContain('create ', 'apply ', 'patch ')
        ->and($result->getErrorOutput())->not->toContain(str_repeat('d', 40), str_repeat('r', 40));
});
