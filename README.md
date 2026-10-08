# Laralog Client

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laranex/laralog-client.svg?style=flat-square)](https://packagist.org/packages/laranex/laralog-client)
[![Tests](https://img.shields.io/github/actions/workflow/status/laranex/laralog-client/tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/laranex/laralog-client/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/laranex/laralog-client.svg?style=flat-square)](https://packagist.org/packages/laranex/laralog-client)
[![License](https://img.shields.io/packagist/l/laranex/laralog-client.svg?style=flat-square)](LICENSE.md)

A Laravel log channel that ships your application logs to a [Laralog Server](https://github.com/naythukhant/laralog) over HTTP. Register it as your default channel or add it to a stack, and every record is posted as JSON (`level`, `message`, normalized `context`) to your server with your team's secret key.

## Documentation

Full documentation lives at **[laranex.vercel.app/laralog-client](https://laranex.vercel.app/laralog-client)**.

## Requirements

- PHP 8.1 or higher
- Laravel 10, 11, 12 or 13
- A running [Laralog Server](https://github.com/naythukhant/laralog)

## Installation

```bash
composer require laranex/laralog-client
```

Publish the config file (optional):

```bash
php artisan vendor:publish --tag="laralog-client-config"
```

## Usage

Point the package at your server and switch the default channel in `.env`:

```env
LOG_CHANNEL=laralog
LARALOG_CLIENT_BASE_URL=https://laralog.example.com
LARALOG_CLIENT_TEAM_SECRET_KEY=your-team-secret
LARALOG_CLIENT_TIMEOUT=5
LARALOG_CLIENT_IGNORE_EXCEPTIONS=true
```

The package registers a `laralog` channel for you. Use it like any other [Laravel log channel](https://laravel.com/docs/logging):

```php
use Illuminate\Support\Facades\Log;

Log::info('Order placed', ['order_id' => $order->id]);

Log::channel('laralog')->error('Payment failed', ['exception' => $e]);
```

To tune the channel or add more of them, declare them in `config/logging.php` with the `laralog` driver:

```php
'channels' => [
    'laralog' => ['driver' => 'laralog', 'level' => 'warning'],

    'stack' => ['driver' => 'stack', 'channels' => ['daily', 'laralog']],
],
```

By default a request the server rejects, or that cannot reach it, never breaks the code that logged: the record is dropped and the first failure is written to PHP's error log. Set `LARALOG_CLIENT_IGNORE_EXCEPTIONS=false` (for example in tests or CI), or `'ignore_exceptions' => false` on a channel, to throw `Laranex\LaralogClient\Exceptions\LaralogClientHttpException` with the HTTP status as its code instead.

## Built for humans and AI agents

The documentation is written for developers, and the package ships an agent skill so AI coding agents use it the way it's meant to be used.

- **Laravel Boost** installs the skill automatically: run `php artisan boost:install` (or `boost:update`).
- **Any other agent** (Claude Code, Codex, Cursor and others): `npx skills add laranex/laralog-client`.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Nay Thu Khant](https://github.com/NayThuKhant)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
