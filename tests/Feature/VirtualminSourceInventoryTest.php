<?php

use App\Hosting\Migrations\VirtualminSource;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->connection = ['url' => 'https://source.example.test:10000', 'username' => 'root', 'password' => 'private-api-password'];
    $this->info = ['command' => 'info', 'status' => 'success', 'output' => "host:\n    virtualmin version: 8.2.0\n    virtualmin edition: GPL\n"];
    $this->rows = [['name' => 'example.test', 'values' => [
        'id' => ['1234567890'], 'username' => ['website'], 'home_directory' => ['/home/website'], 'type' => ['Top-level server'],
        'features' => ['dir web ssl dns mail mysql'], 'plugins' => ['virtualmin-awstats'],
        'password' => ['private-source-password'], 'hashed_password' => ['private-password-hash'],
    ]]];
    $this->apiFailure = false;
    Http::preventStrayRequests();
    Http::fake(function (Request $request) {
        expect($request->method())->toBe('POST')->and($request->url())->toBe('https://source.example.test:10000/virtual-server/remote.cgi');
        expect($request->header('Authorization'))->toBe(['Basic '.base64_encode('root:private-api-password')]);
        if ($this->apiFailure) {
            return Http::response(['command' => 'info', 'status' => 'failure', 'error' => 'private-source-password'], 200);
        }

        return $request['program'] === 'info' ? Http::response($this->info) : Http::response(['command' => 'list-domains', 'status' => 'success', 'data' => $this->rows]);
    });
});

it('inspects Virtualmin 8 GPL and Pro metadata without exposing source passwords', function (string $edition): void {
    $this->info['output'] = "host:\n    virtualmin version: 8.2.0.pro-1\n    virtualmin edition: {$edition}\n";
    $result = app(VirtualminSource::class)->inspect($this->connection, ['example.test']);
    expect($result['version'])->toBe('8.2.0.pro-1')->and($result['edition'])->toBe($edition === 'GPL' ? 'gpl' : 'pro')
        ->and($result['domains'][0])->toMatchArray(['domain' => 'example.test', 'source_id' => '1234567890', 'username' => 'website', 'home' => '/home/website'])
        ->and(json_encode($result))->not->toContain('private-api-password', 'private-source-password', 'private-password-hash');
    Http::assertSentCount(2);
    Http::assertNotSent(fn (Request $request): bool => ! in_array($request['program'], ['info', 'list-domains'], true));
})->with(['GPL', 'Pro', 'Professional']);

it('requires an explicit whole-server approval before returning every source domain', function (): void {
    $other = $this->rows[0];
    $other['name'] = 'other.test';
    $other['values']['id'] = ['2234567890'];
    $this->rows[] = $other;
    $selected = app(VirtualminSource::class)->inspect($this->connection, ['example.test']);
    expect($selected['excluded_domains'])->toBe(1)->and(array_column($selected['domains'], 'domain'))->toBe(['example.test']);
    expect(app(VirtualminSource::class)->inspect($this->connection, ['*'])['domains'])->toHaveCount(2);
});

it('rejects unsafe origins and credentials before sending any request', function (string $problem): void {
    match ($problem) {
        'plaintext' => $this->connection['url'] = 'http://source.example.test',
        'embedded-credentials' => $this->connection['url'] = 'https://root:password@source.example.test',
        'wrong-path' => $this->connection['url'] = 'https://source.example.test/other-api',
        'query' => $this->connection['url'] = 'https://source.example.test?key=private',
        'header-injection' => $this->connection['password'] = "private\r\nInjected: true",
        'missing-password' => $this->connection['password'] = '',
    };
    expect(fn () => app(VirtualminSource::class)->inspect($this->connection, ['example.test']))->toThrow(InvalidArgumentException::class);
    Http::assertNothingSent();
})->with(['plaintext', 'embedded-credentials', 'wrong-path', 'query', 'header-injection', 'missing-password']);

it('rejects missing, invalid, duplicated or ambiguous source-domain approvals', function (array $domains): void {
    expect(fn () => app(VirtualminSource::class)->inspect($this->connection, $domains))->toThrow(InvalidArgumentException::class);
    Http::assertNothingSent();
})->with([[[]], [['*.example.test']], [['example..test']], [['example.test', 'EXAMPLE.TEST']], [['*', 'example.test']]]);

it('sanitizes remote API failures', function (): void {
    $this->apiFailure = true;
    try {
        app(VirtualminSource::class)->inspect($this->connection, ['example.test']);
        $this->fail('Source API failure must not be accepted.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->not->toContain('private-source-password', 'private-api-password');
    }
});

it('does not accept another Virtualmin major version', function (): void {
    $this->info['output'] = "virtualmin version: 7.9.0\nvirtualmin edition: GPL\n";
    expect(fn () => app(VirtualminSource::class)->inspect($this->connection, ['example.test']))->toThrow(RuntimeException::class, 'Virtualmin 8');
});

it('fails closed when an approved source domain is missing', function (): void {
    expect(fn () => app(VirtualminSource::class)->inspect($this->connection, ['missing.test']))->toThrow(RuntimeException::class, 'absent');
});

it('rejects duplicate source identities and malformed mandatory metadata', function (string $problem): void {
    match ($problem) {
        'duplicate' => $this->rows[] = $this->rows[0],
        'missing-id' => $this->rows[0]['values']['id'] = [],
        'duplicate-id-values' => $this->rows[0]['values']['id'] = ['123', '456'],
        'root-home' => $this->rows[0]['values']['home_directory'] = ['/'],
        'traversal-home' => $this->rows[0]['values']['home_directory'] = ['/home/../root'],
        'unknown-type' => $this->rows[0]['values']['type'] = ['unsupported'],
    };
    expect(fn () => app(VirtualminSource::class)->inspect($this->connection, ['example.test']))->toThrow(RuntimeException::class);
})->with(['duplicate', 'missing-id', 'duplicate-id-values', 'root-home', 'traversal-home', 'unknown-type']);

it('requires parent and alias-target domains within the approved migration scope', function (string $field): void {
    $this->rows[0]['values'][$field] = ['outside.test'];
    expect(fn () => app(VirtualminSource::class)->inspect($this->connection, ['example.test']))->toThrow(RuntimeException::class, 'parent and alias-target');
})->with(['parent_domain', 'real_domain']);

it('rejects duplicate numeric source IDs across distinct domains', function (): void {
    $this->rows[] = [...$this->rows[0], 'name' => 'another.test'];
    expect(fn () => app(VirtualminSource::class)->inspect($this->connection, ['*']))->toThrow(RuntimeException::class, 'duplicate numeric');
});

it('rejects circular domain dependencies before allowing migration planning', function (): void {
    $this->rows[0]['values']['type'] = ['Alias'];
    $this->rows[0]['values']['real_domain'] = ['another.test'];
    $other = $this->rows[0];
    $other['name'] = 'another.test';
    $other['values']['id'] = ['2234567890'];
    $other['values']['real_domain'] = ['example.test'];
    $this->rows[] = $other;
    expect(fn () => app(VirtualminSource::class)->inspect($this->connection, ['*']))->toThrow(RuntimeException::class, 'cyclic');
});

it('rejects aliases without their target instead of importing them as independent websites', function (): void {
    $this->rows[0]['values']['type'] = ['Alias'];
    expect(fn () => app(VirtualminSource::class)->inspect($this->connection, ['example.test']))->toThrow(RuntimeException::class, 'must identify');
});

it('rejects non-list and non-string domain scopes before making requests', function (array $domains): void {
    expect(fn () => app(VirtualminSource::class)->inspect($this->connection, $domains))->toThrow(InvalidArgumentException::class);
    Http::assertNothingSent();
})->with([[['domain' => 'example.test']], [[123]], [[['domain' => 'example.test']]]]);

it('sanitizes connection failures without retaining the upstream exception chain', function (): void {
    Http::fake(['*' => Http::failedConnection('private-api-password')]);
    try {
        app(VirtualminSource::class)->inspect($this->connection, ['example.test']);
        $this->fail('Connection failure must not be accepted.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->not->toContain('private-api-password')->and($exception->getPrevious())->toBeNull();
    }
});
