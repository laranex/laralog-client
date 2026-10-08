<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laranex\LaralogClient\Exceptions\LaralogClientHttpException;
use Laranex\LaralogClient\LaralogHandler;
use Monolog\Logger;

function fakeLaralogServer(int $status = 201, string $body = '{"ok":true}'): void
{
    Http::fake(['laralog.test/*' => Http::response($body, $status)]);
}

it('posts a record as JSON to the Laralog server with the team secret', function (): void {
    fakeLaralogServer();
    Log::channel('laralog')->info('Order placed', ['order_id' => 42]);

    Http::assertSentCount(1);
    Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://laralog.test/api/logs'
        && $request->method() === 'POST'
        && $request->hasHeader('X-TEAM-SECRET-KEY', 'team-secret')
        && $request->hasHeader('X-Requested-With', 'XMLHttpRequest')
        && $request->hasHeader('Content-Type', 'application/json')
        && $request->hasHeader('Accept', 'application/json')
        && $request->data() === ['level' => 'INFO', 'message' => 'Order placed', 'context' => ['order_id' => 42]]);
});

it('sends the Monolog level name for every level', function (string $level, string $expected): void {
    fakeLaralogServer();
    Log::channel('laralog')->log($level, 'hello');

    Http::assertSent(fn (HttpRequest $request): bool => $request['level'] === $expected && $request['message'] === 'hello');
})->with([
    ['debug', 'DEBUG'],
    ['info', 'INFO'],
    ['notice', 'NOTICE'],
    ['warning', 'WARNING'],
    ['error', 'ERROR'],
    ['critical', 'CRITICAL'],
    ['alert', 'ALERT'],
    ['emergency', 'EMERGENCY'],
]);

it('normalizes exceptions and dates in the context before sending', function (): void {
    fakeLaralogServer();
    Log::channel('laralog')->error('Boom', [
        'exception' => new RuntimeException('Something broke', 7),
        'at' => new DateTimeImmutable('2026-10-08 12:00:00', new DateTimeZone('UTC')),
        'nested' => ['flag' => true, 'ratio' => 0.5],
    ]);

    Http::assertSent(function (HttpRequest $request): bool {
        $context = $request['context'];

        return $context['exception']['class'] === RuntimeException::class
            && $context['exception']['message'] === 'Something broke'
            && $context['exception']['code'] === 7
            && is_string($context['exception']['file'])
            && str_starts_with($context['at'], '2026-10-08T12:00:00')
            && $context['nested'] === ['flag' => true, 'ratio' => 0.5];
    });
});

it('interpolates nothing and sends an empty context as an empty array', function (): void {
    fakeLaralogServer();
    Log::channel('laralog')->warning('plain {message}');

    Http::assertSent(fn (HttpRequest $request): bool => $request['message'] === 'plain {message}' && $request['context'] === []);
});

it('drops records below the configured channel level', function (): void {
    fakeLaralogServer();
    config()->set('logging.channels.laralog', ['driver' => 'laralog', 'level' => 'error']);

    Log::channel('laralog')->info('ignored');
    Log::channel('laralog')->error('kept');

    Http::assertSentCount(1);
    Http::assertSent(fn (HttpRequest $request): bool => $request['message'] === 'kept');
});

it('works inside a stack channel', function (): void {
    fakeLaralogServer();
    config()->set('logging.channels.stacked', ['driver' => 'stack', 'channels' => ['laralog', 'null']]);

    Log::channel('stacked')->notice('stacked');

    Http::assertSent(fn (HttpRequest $request): bool => $request['level'] === 'NOTICE' && $request['message'] === 'stacked');
});

it('throws when the Laralog server rejects the record and exceptions are not ignored', function (): void {
    config()->set('laralog-client.ignore_exceptions', false);
    fakeLaralogServer(401, '{"message":"Unauthenticated."}');

    try {
        Log::channel('laralog')->info('rejected');
        $this->fail('Expected a LaralogClientHttpException');
    } catch (LaralogClientHttpException $exception) {
        expect($exception->getCode())->toBe(401)
            ->and($exception->getMessage())->toBe('Request to the Laralog server failed with status 401: {"message":"Unauthenticated."}');
    }
});

it('throws when the Laralog server returns a server error and exceptions are not ignored', function (): void {
    config()->set('laralog-client.ignore_exceptions', false);
    fakeLaralogServer(500, 'boom');

    Log::channel('laralog')->info('failed');
})->throws(LaralogClientHttpException::class, 'failed with status 500: boom');

it('wraps connection failures in LaralogClientHttpException when exceptions are not ignored', function (): void {
    config()->set('laralog-client.ignore_exceptions', false);
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    try {
        Log::channel('laralog')->info('offline');
        $this->fail('Expected a LaralogClientHttpException');
    } catch (LaralogClientHttpException $exception) {
        expect($exception->getMessage())->toBe('Could not connect to the Laralog server: Connection refused')
            ->and($exception->getPrevious())->toBeInstanceOf(ConnectionException::class);
    }
});

it('never lets a Laralog failure break the caller by default', function (): void {
    $errorLog = tempnam(sys_get_temp_dir(), 'laralog');
    $previous = ini_set('error_log', (string) $errorLog);

    try {
        fakeLaralogServer(500, 'boom');
        Log::channel('laralog')->info('first');
        Log::channel('laralog')->info('second');

        Http::fake(fn () => throw new ConnectionException('Connection refused'));
        Log::channel('laralog')->error('offline');

        $contents = (string) file_get_contents((string) $errorLog);
    } finally {
        ini_set('error_log', (string) $previous);
        @unlink((string) $errorLog);
    }

    expect(substr_count($contents, '[laralog-client]'))->toBe(1)
        ->and($contents)->toContain(LaralogClientHttpException::class)
        ->and($contents)->toContain('failed with status 500: boom');
});

it('lets a channel override the ignore_exceptions config', function (): void {
    config()->set('logging.channels.laralog', ['driver' => 'laralog', 'ignore_exceptions' => false]);
    fakeLaralogServer(503, 'down');

    Log::channel('laralog')->info('failed');
})->throws(LaralogClientHttpException::class, 'failed with status 503: down');

it('reads ignore_exceptions from the package config', function (bool $ignore): void {
    config()->set('laralog-client.ignore_exceptions', $ignore);

    $handler = Log::channel('laralog')->getLogger()->getHandlers()[0];

    expect($handler)->toBeInstanceOf(LaralogHandler::class)
        ->and($handler->ignoresExceptions())->toBe($ignore);
})->with([true, false]);

it('resolves the channel to a Monolog logger using the Laralog handler', function (): void {
    $logger = Log::channel('laralog')->getLogger();

    expect($logger)->toBeInstanceOf(Logger::class)
        ->and($logger->getName())->toBe('laralog')
        ->and($logger->getHandlers())->toHaveCount(1)
        ->and($logger->getHandlers()[0])->toBeInstanceOf(LaralogHandler::class);
});

it('names the logger after the channel config', function (): void {
    config()->set('logging.channels.laralog', ['driver' => 'laralog', 'name' => 'production']);

    expect(Log::channel('laralog')->getLogger()->getName())->toBe('production');
});

it('lets the host app define its own channel with the laralog driver', function (): void {
    fakeLaralogServer();
    config()->set('logging.channels.audit', ['driver' => 'laralog', 'level' => 'warning', 'bubble' => false]);

    Log::channel('audit')->warning('audited');
    Log::channel('audit')->info('skipped');

    /** @var LaralogHandler $handler */
    $handler = Log::channel('audit')->getLogger()->getHandlers()[0];

    Http::assertSentCount(1);
    expect($handler->getBubble())->toBeFalse();
});

it('accepts the channel level as a name in any case or as a Monolog number', function (mixed $level): void {
    fakeLaralogServer();
    config()->set('logging.channels.laralog', ['driver' => 'laralog', 'level' => $level]);

    Log::channel('laralog')->warning('skipped');
    Log::channel('laralog')->error('sent');

    Http::assertSentCount(1);
})->with(['error', 'ERROR', 'Error', 400]);

it('rejects an unknown channel level so Laravel falls back to its emergency logger', function (mixed $level): void {
    fakeLaralogServer();
    config()->set('logging.channels.laralog', ['driver' => 'laralog', 'level' => $level]);

    $handlers = Log::channel('laralog')->getLogger()->getHandlers();

    expect($handlers)->not->toBeEmpty()
        ->and($handlers[0])->not->toBeInstanceOf(LaralogHandler::class);
    Http::assertNothingSent();
})->with(['verbose', 123, true]);
