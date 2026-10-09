---
name: laralog-client
description: >
  Ship a Laravel application's logs to a Laralog server with the laranex/laralog-client "laralog" log channel.
  Use when configuring LOG_CHANNEL=laralog, adding the laralog driver to config/logging.php, or testing code that logs to Laralog.
license: MIT
metadata:
  author: Nay Thu Khant
---

# Laralog Client

## When to use

- The application should send its logs to a Laralog server.
- You are adding, tuning or stacking a log channel that uses the `laralog` driver.
- You are writing tests for code that logs through the `laralog` channel.

## Install

```bash
composer require laranex/laralog-client
```

The service provider is auto-discovered. Publish the config only if you need to change it:

```bash
php artisan vendor:publish --tag="laralog-client-config"
```

## Configure

Set these in `.env` (they map to `config/laralog-client.php`):

```env
LARALOG_CLIENT_BASE_URL=https://laralog.example.com   # base_url: server origin, no path
LARALOG_CLIENT_TEAM_SECRET_KEY=your-team-secret       # team_secret_key
LARALOG_CLIENT_TIMEOUT=5                              # timeout in seconds (default 5)
LARALOG_CLIENT_IGNORE_EXCEPTIONS=true                 # ignore_exceptions (default true)
```

- `base_url` and `team_secret_key` are required. Without them, building the channel throws `Laranex\LaralogClient\Exceptions\LaralogClientHttpException`; Laravel catches it and writes to its emergency logger (`storage/logs/laravel.log`) instead, so nothing reaches Laralog.
- Records are posted as JSON `{level, message, context}` to `{base_url}/api/logs` with the `X-TEAM-SECRET-KEY` header.

## Use

### Make Laralog the default channel

The package registers a `logging.channels.laralog` channel (`'driver' => 'laralog'`) when the app does not define one:

```env
LOG_CHANNEL=laralog
```

```php
use Illuminate\Support\Facades\Log;

Log::info('Order placed', ['order_id' => $order->id]);
Log::channel('laralog')->error('Payment failed', ['exception' => $e]);
```

Context is normalized with Monolog's `NormalizerFormatter`, so exceptions, dates and objects arrive as JSON-safe values.

### Tune the channel or stack it

Declare the channel in `config/logging.php` to set options. Supported options: `level`, `bubble`, `name`, `ignore_exceptions`.

```php
'channels' => [
    'laralog' => ['driver' => 'laralog', 'level' => 'warning'],

    'stack' => ['driver' => 'stack', 'channels' => ['daily', 'laralog']],
],
```

Any number of channels may use `'driver' => 'laralog'`.

### Handle failures

- By default (`ignore_exceptions` true) a rejected or unreachable request never breaks the code that logged: the record is dropped and the first failure is written to PHP's error log.
- With `LARALOG_CLIENT_IGNORE_EXCEPTIONS=false`, or `'ignore_exceptions' => false` on a channel, failures throw `LaralogClientHttpException`. `getCode()` is the HTTP status, or `0` when the server could not be reached.

## Test your app

Fake the Laralog server with Laravel's HTTP client fake and assert on the posted record:

```php
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

Http::fake(['laralog.example.com/*' => Http::response([], 201)]);

Log::channel('laralog')->warning('Stock low', ['sku' => 'A-1']);

Http::assertSent(fn (Request $request): bool => $request->url() === 'https://laralog.example.com/api/logs'
    && $request['level'] === 'WARNING'
    && $request['message'] === 'Stock low');
```

Set `laralog-client.ignore_exceptions` to `false` in tests so a rejected or unreachable request fails the test instead of being dropped.

## Avoid

- Posting to `/api/logs` yourself or building `LaralogHandler`/`LaralogClient` by hand; use the `laralog` driver.
- Putting the team secret key in `config/logging.php` or in code; keep it in `.env`.
- Sending `debug` records to Laralog in production without a `level` on the channel; every record is one HTTP request.
