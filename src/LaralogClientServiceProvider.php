<?php

declare(strict_types=1);

namespace Laranex\LaralogClient;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Laranex\LaralogClient\Exceptions\LaralogClientHttpException;
use Monolog\Level;
use Monolog\Logger;

class LaralogClientServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laralog-client.php', 'laralog-client');

        $this->app->singleton(LaralogClient::class, fn (Container $app): LaralogClient => new LaralogClient(
            $app->make(HttpFactory::class),
            $this->requiredConfig($app, 'base_url'),
            $this->requiredConfig($app, 'team_secret_key'),
            (int) $this->config($app, 'timeout', 5),
        ));

        $this->registerChannel();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/laralog-client.php' => $this->app->configPath('laralog-client.php'),
        ], ['laralog-client', 'laralog-client-config']);
    }

    /**
     * Register the "laralog" log driver and a default "laralog" channel using it.
     */
    private function registerChannel(): void
    {
        $config = $this->app->make(ConfigRepository::class);

        if (! $config->has('logging.channels.laralog')) {
            $config->set('logging.channels.laralog', ['driver' => 'laralog']);
        }

        // LogManager rebinds driver closures to itself, so capture the provider's resolver up front.
        $level = fn (mixed $level): Level => $this->level($level);
        $ignoreExceptions = fn (Container $app, array $config): bool => (bool) ($config['ignore_exceptions'] ?? $this->config($app, 'ignore_exceptions', true));

        $this->app->make(LogManager::class)->extend('laralog', function (Container $app, array $config) use ($level, $ignoreExceptions): Logger {
            $handler = new LaralogHandler(
                $app->make(LaralogClient::class),
                $level($config['level'] ?? Level::Debug),
                (bool) ($config['bubble'] ?? true),
                $ignoreExceptions($app, $config),
            );

            $name = $config['name'] ?? 'laralog';

            return new Logger(is_string($name) ? $name : 'laralog', [$handler]);
        });
    }

    /**
     * Turn the channel's "level" option (a Level, a PSR-3/Monolog name or a Monolog number) into a Level.
     */
    private function level(mixed $level): Level
    {
        if ($level instanceof Level) {
            return $level;
        }

        if (is_int($level) && ($resolved = Level::tryFrom($level)) !== null) {
            return $resolved;
        }

        if (is_string($level)) {
            foreach (Level::cases() as $case) {
                if (strcasecmp($case->name, $level) === 0) {
                    return $case;
                }
            }
        }

        throw new InvalidArgumentException('Invalid log level for the laralog channel.');
    }

    private function config(Container $app, string $key, mixed $default = null): mixed
    {
        return $app->make(ConfigRepository::class)->get('laralog-client.'.$key, $default);
    }

    private function requiredConfig(Container $app, string $key): string
    {
        $value = $this->config($app, $key);

        if (! is_string($value) || $value === '') {
            throw LaralogClientHttpException::missingConfiguration($key);
        }

        return $value;
    }
}
