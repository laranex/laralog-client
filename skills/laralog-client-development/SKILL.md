---
name: laralog-client-development
description: >
  Send a Laravel application's logs to a Laralog server with the laranex/laralog-client log channel.
license: MIT
metadata:
  author: Nay Thu Khant
---

# Laralog Client

Use this skill when a Laravel application should ship its logs to a Laralog server through laranex/laralog-client.

## Primary Goal

- route logs to Laralog through the package's `laralog` channel instead of hand-written HTTP calls

## Workflow

### 1. Configure the server

- set `LARALOG_CLIENT_BASE_URL` (server origin, no path) and `LARALOG_CLIENT_TEAM_SECRET_KEY` in `.env`; both are required and resolving the channel without them throws `LaralogClientHttpException`
- optionally set `LARALOG_CLIENT_TIMEOUT` (seconds, default `5`)
- `LARALOG_CLIENT_IGNORE_EXCEPTIONS` (default `true`) swallows failed requests so logging never breaks the caller; set it to `false` in tests/CI to surface failures
- publish the config only when it must change: `php artisan vendor:publish --tag="laralog-client-config"`

### 2. Enable the channel

- the package registers `logging.channels.laralog` (`driver => 'laralog'`) automatically; `LOG_CHANNEL=laralog` is enough
- to set a minimum level, a logger name, `bubble` or `ignore_exceptions`, declare the channel in `config/logging.php`: `'laralog' => ['driver' => 'laralog', 'level' => 'warning']`
- any extra channel may use `'driver' => 'laralog'`, and the channel can be a member of a `stack`

### 3. Log

- use the `Log` facade as usual; each record is posted as JSON `{level, message, context}` to `{base_url}/api/logs` with the `X-TEAM-SECRET-KEY` header
- exceptions, dates and objects in the context are normalised by Monolog's `NormalizerFormatter`, so `['exception' => $e]` arrives as class, message, code, file and trace
- non-2xx responses and connection failures are swallowed by default (first failure goes to PHP's error log); with `ignore_exceptions` false (config or per-channel option) they throw `Laranex\LaralogClient\Exceptions\LaralogClientHttpException` (`getCode()` is the HTTP status, `0` for connection failures)

### 4. Test

- fake the server with `Http::fake(['laralog.example.com/*' => Http::response([], 201)])` and assert with `Http::assertSent(fn ($request) => $request['message'] === '...')`

## Rules, References, and Templates

- no additional resource files for this skill

## Examples

- Default channel: `LOG_CHANNEL=laralog` then `Log::info('Order placed', ['order_id' => $order->id])`
- Errors only, alongside daily files: `'stack' => ['driver' => 'stack', 'channels' => ['daily', 'laralog']]` with `'laralog' => ['driver' => 'laralog', 'level' => 'error']`

## Anti-patterns

- do not post to `/api/logs` yourself or instantiate `LaralogHandler` manually; use the `laralog` driver
- do not put the secret key in `config/logging.php`; keep it in `.env` under `LARALOG_CLIENT_TEAM_SECRET_KEY`
- do not log at `debug` level to Laralog in production without a `level` on the channel; every record is one HTTP request
