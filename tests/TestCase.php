<?php

declare(strict_types=1);

namespace Laranex\LaralogClient\Tests;

use Laranex\LaralogClient\LaralogClientServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaralogClientServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('laralog-client.base_url', 'https://laralog.test/');
        $app['config']->set('laralog-client.team_secret_key', 'team-secret');
    }
}
