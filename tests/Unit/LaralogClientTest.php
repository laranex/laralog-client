<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request as HttpRequest;
use Laranex\LaralogClient\Exceptions\LaralogClientHttpException;
use Laranex\LaralogClient\LaralogClient;

function fakeHttp(mixed $response = null): HttpFactory
{
    $http = new HttpFactory;
    $http->fake($response === null ? null : ['*' => $response]);

    return $http;
}

it('builds the endpoint from the base URL without doubling slashes', function (string $baseUrl): void {
    expect((new LaralogClient(fakeHttp(), $baseUrl, 'secret'))->endpoint())->toBe('https://logs.example.com/api/logs');
})->with(['https://logs.example.com', 'https://logs.example.com/', 'https://logs.example.com//']);

it('posts the record with the secret header and JSON body', function (): void {
    $http = fakeHttp(HttpFactory::response(['stored' => true]));

    (new LaralogClient($http, 'https://logs.example.com', 'secret', 3))
        ->send(['level' => 'ERROR', 'message' => 'oops', 'context' => ['a' => 1]]);

    $http->assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://logs.example.com/api/logs'
        && $request->hasHeader('X-TEAM-SECRET-KEY', 'secret')
        && $request->body() === '{"level":"ERROR","message":"oops","context":{"a":1}}');
});

it('accepts every 2xx status', function (int $status): void {
    $http = fakeHttp(HttpFactory::response('', $status));

    (new LaralogClient($http, 'https://logs.example.com', 'secret'))->send(['level' => 'INFO', 'message' => 'm', 'context' => []]);

    $http->assertSentCount(1);
})->with([200, 201, 202, 204]);

it('throws with the status and body on a non-2xx response', function (int $status): void {
    $http = fakeHttp(HttpFactory::response('nope', $status));

    try {
        (new LaralogClient($http, 'https://logs.example.com', 'secret'))->send(['level' => 'INFO', 'message' => 'm', 'context' => []]);
        $this->fail('Expected a LaralogClientHttpException');
    } catch (LaralogClientHttpException $exception) {
        expect($exception->getCode())->toBe($status)
            ->and($exception->getMessage())->toBe("Request to the Laralog server failed with status {$status}: nope");
    }
})->with([400, 401, 403, 404, 422, 500, 503]);

it('wraps connection exceptions', function (): void {
    $http = new HttpFactory;
    $http->fake(fn () => throw new ConnectionException('timed out'));

    (new LaralogClient($http, 'https://logs.example.com', 'secret'))->send(['level' => 'INFO', 'message' => 'm', 'context' => []]);
})->throws(LaralogClientHttpException::class, 'Could not connect to the Laralog server: timed out');
