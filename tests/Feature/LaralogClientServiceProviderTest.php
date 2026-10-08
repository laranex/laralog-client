<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Laranex\LaralogClient\Exceptions\LaralogClientHttpException;
use Laranex\LaralogClient\LaralogClient;
use Laranex\LaralogClient\LaralogClientServiceProvider;

it('merges the package config with the host config', function (): void {
    expect(config('laralog-client.base_url'))->toBe('https://laralog.test/')
        ->and(config('laralog-client.team_secret_key'))->toBe('team-secret')
        ->and(config('laralog-client.timeout'))->toBe(5);
});

it('registers a default laralog channel', function (): void {
    expect(config('logging.channels.laralog'))->toBe(['driver' => 'laralog']);
});

it('binds the client as a singleton built from the config', function (): void {
    $client = app(LaralogClient::class);

    expect($client)->toBe(app(LaralogClient::class))
        ->and($client->endpoint())->toBe('https://laralog.test/api/logs');
});

it('passes the configured timeout to the HTTP client', function (): void {
    config()->set('laralog-client.timeout', 12);
    $timeout = null;
    Http::fake(function ($request, array $options) use (&$timeout) {
        $timeout = $options['timeout'];

        return Http::response();
    });

    app(LaralogClient::class)->send(['level' => 'INFO', 'message' => 'm', 'context' => []]);

    expect($timeout)->toBe(12);
});

it('fails loudly when the base URL is missing', function (): void {
    config()->set('laralog-client.base_url', null);

    app(LaralogClient::class);
})->throws(LaralogClientHttpException::class, 'The "laralog-client.base_url" config value is not set.');

it('fails loudly when the team secret key is missing', function (): void {
    config()->set('laralog-client.team_secret_key', '');

    app(LaralogClient::class);
})->throws(LaralogClientHttpException::class, 'The "laralog-client.team_secret_key" config value is not set.');

it('publishes the config file under both tags', function (string $tag): void {
    $paths = ServiceProvider::pathsToPublish(LaralogClientServiceProvider::class, $tag);

    expect($paths)->toHaveCount(1)
        ->and(realpath((string) array_key_first($paths)))->toBe(realpath(__DIR__.'/../../config/laralog-client.php'))
        ->and(array_values($paths))->toBe([config_path('laralog-client.php')]);
})->with(['laralog-client', 'laralog-client-config']);
