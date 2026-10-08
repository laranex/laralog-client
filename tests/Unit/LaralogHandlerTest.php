<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request as HttpRequest;
use Laranex\LaralogClient\Exceptions\LaralogClientHttpException;
use Laranex\LaralogClient\LaralogClient;
use Laranex\LaralogClient\LaralogHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Logger;

function handlerWithFake(int|string $level = Logger::DEBUG, bool $bubble = true): array
{
    $http = new HttpFactory;
    $http->fake();

    return [$http, new LaralogHandler(new LaralogClient($http, 'https://logs.example.com', 'secret'), $level, $bubble)];
}

it('runs against Monolog 3', function (): void {
    expect(Logger::API)->toBe(3);
});

it('also accepts Monolog 2 style array records', function (): void {
    [$http, $handler] = handlerWithFake();

    (fn (array $record) => $this->write($record))->call($handler, [
        'level_name' => 'ALERT',
        'message' => 'legacy',
        'context' => ['k' => 'v'],
        'formatted' => ['level_name' => 'ALERT', 'message' => 'legacy', 'context' => ['k' => 'v']],
    ]);

    $http->assertSent(fn (HttpRequest $request): bool => $request->data() === ['level' => 'ALERT', 'message' => 'legacy', 'context' => ['k' => 'v']]);
});

it('exposes its client and uses the normalizer formatter by default', function (): void {
    [, $handler] = handlerWithFake();

    expect($handler->client())->toBeInstanceOf(LaralogClient::class)
        ->and($handler->getFormatter())->toBeInstanceOf(NormalizerFormatter::class);
});

it('handles a record pushed straight through Monolog', function (): void {
    [$http, $handler] = handlerWithFake();
    $logger = new Logger('app', [$handler]);

    $logger->warning('Disk almost full', ['free' => '2%']);

    $http->assertSent(fn (HttpRequest $request): bool => $request->data() === [
        'level' => 'WARNING',
        'message' => 'Disk almost full',
        'context' => ['free' => '2%'],
    ]);
});

it('normalizes objects in the context', function (): void {
    [$http, $handler] = handlerWithFake();
    $logger = new Logger('app', [$handler]);

    $logger->error('failed', ['error' => new LogicException('bad state'), 'when' => new DateTimeImmutable('2026-01-02 03:04:05', new DateTimeZone('UTC'))]);

    $http->assertSent(fn (HttpRequest $request): bool => $request['context']['error']['class'] === LogicException::class
        && $request['context']['error']['message'] === 'bad state'
        && str_starts_with($request['context']['when'], '2026-01-02T03:04:05'));
});

it('falls back to the raw record when a custom formatter does not produce an array', function (): void {
    [$http, $handler] = handlerWithFake();
    $handler->setFormatter(new LineFormatter);
    $logger = new Logger('app', [$handler]);

    $logger->info('raw', ['k' => 'v']);

    $http->assertSent(fn (HttpRequest $request): bool => $request->data() === ['level' => 'INFO', 'message' => 'raw', 'context' => ['k' => 'v']]);
});

it('respects the minimum level and bubble flag', function (): void {
    [$http, $handler] = handlerWithFake('error', false);
    $logger = new Logger('app', [$handler]);

    $logger->info('ignored');
    $logger->critical('sent');

    $http->assertSentCount(1);
    expect($handler->getBubble())->toBeFalse();
});

it('filters by level on Monolog 3 and 2 alike', function (): void {
    [$http, $handler] = handlerWithFake('error');
    $logger = new Logger('app', [$handler]);

    $logger->debug('ignored');
    $logger->notice('ignored too');
    $logger->error('sent');
    $logger->emergency('sent as well');

    $http->assertSentCount(2);
});

it('ignores exceptions by default and throws when asked to', function (): void {
    $http = new HttpFactory;
    $http->fake(fn () => $http->response('boom', 500));
    $client = new LaralogClient($http, 'https://logs.example.com', 'secret');

    $errorLog = tempnam(sys_get_temp_dir(), 'laralog');
    $previous = ini_set('error_log', (string) $errorLog);

    try {
        $quiet = new LaralogHandler($client);
        (new Logger('app', [$quiet]))->error('swallowed');
    } finally {
        ini_set('error_log', (string) $previous);
        @unlink((string) $errorLog);
    }

    expect($quiet->ignoresExceptions())->toBeTrue();

    (new Logger('app', [new LaralogHandler($client, ignoreExceptions: false)]))->error('thrown');
})->throws(LaralogClientHttpException::class, 'failed with status 500: boom');
