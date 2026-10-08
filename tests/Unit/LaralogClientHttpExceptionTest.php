<?php

declare(strict_types=1);

use Laranex\LaralogClient\Exceptions\LaralogClientHttpException;

it('describes an unexpected status with the response body', function (): void {
    $exception = LaralogClientHttpException::unexpectedStatus(422, '{"errors":{}}');

    expect($exception)->toBeInstanceOf(RuntimeException::class)
        ->and($exception->getCode())->toBe(422)
        ->and($exception->getMessage())->toBe('Request to the Laralog server failed with status 422: {"errors":{}}');
});

it('keeps the previous exception on connection failures', function (): void {
    $previous = new RuntimeException('dns failure');
    $exception = LaralogClientHttpException::connectionFailed($previous);

    expect($exception->getMessage())->toBe('Could not connect to the Laralog server: dns failure')
        ->and($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBe($previous);
});

it('names the missing config key', function (): void {
    expect(LaralogClientHttpException::missingConfiguration('team_secret_key')->getMessage())
        ->toBe('The "laralog-client.team_secret_key" config value is not set.');
});
