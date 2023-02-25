<?php

namespace Laranex\LaralogClient;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Monolog\Logger;

class LaralogClientServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laralog-client.php'),
            ], 'laralog-client');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laralog-client');

        $this->app->booted(function () {
            $config = [
                'driver' => 'laralog',
                'name' => 'laralog',
            ];
            config()->set('logging.channels.laralog', $config);
        });

        Log::extend('laralog', function ($app, $config) {
            $logger = new Logger($config['name']);
            $handler = new LaralogClient(
                $config['level'] ?? Logger::DEBUG
            );
            $logger->pushHandler($handler);

            return $logger;
        });
    }
}
