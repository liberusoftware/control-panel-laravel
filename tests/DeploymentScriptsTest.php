<?php

use Symfony\Component\Process\Process;

beforeEach(function (): void {
    $this->projectRoot = dirname(__DIR__);
    $this->sandbox = sys_get_temp_dir().'/panel-deployment-'.bin2hex(random_bytes(8));
    mkdir($this->sandbox.'/bin', 0700, true);
    $this->stubCommand = function (string $name, string $body): void {
        $path = $this->sandbox.'/bin/'.$name;
        file_put_contents($path, "#!/bin/sh\nset -eu\n".$body."\n");
        chmod($path, 0700);
    };
    $this->runScript = function (string $script, array $arguments = [], array $environment = []): Process {
        $process = new Process(['bash', $this->projectRoot.'/'.$script, ...$arguments], $this->sandbox, [
            'PATH' => $this->sandbox.'/bin:'.getenv('PATH'),
            'CONTAINER_MODE' => false,
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
