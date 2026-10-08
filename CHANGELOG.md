# Changelog

All notable changes to `laralog-client` will be documented in this file

## v4.0.0 - Unreleased

Versions 2 and 3 were never released; v4.0.0 follows v1.0.1 directly so every Laranex package shares the same major.

### Changed
- Requires PHP 8.1+ and supports Laravel 10 through 13.
- Rebuilt on the official Laravel package skeleton (Pest, PHPStan, Pint, Testbench workbench, GitHub Actions matrix).
- Requires Monolog 3 (the version Laravel 10+ ships); v1.0.1 only worked with Monolog 2.
- Records are sent through Laravel's HTTP client instead of `file_get_contents()`, so `Http::fake()` works in your tests and the request honours the new `timeout` config value (`LARALOG_CLIENT_TIMEOUT`, default 5 seconds).
- Exceptions, dates and objects in the log context are normalised with Monolog's `NormalizerFormatter` before they are sent instead of being JSON-encoded as `{}`.
- The handler class `Laranex\LaralogClient\LaralogClient` was renamed to `Laranex\LaralogClient\LaralogHandler`; `Laranex\LaralogClient\LaralogClient` is now the HTTP client that posts records to the server.
- `LaralogClientHttpException` extends `RuntimeException`, carries the HTTP status as its code, keeps the underlying connection exception as `getPrevious()`, and is also thrown when `base_url` or `team_secret_key` is missing.
- The `laralog` channel honours `level`, `bubble` and `name` from `config/logging.php`, and any extra channel may use `'driver' => 'laralog'`. An unknown `level` is rejected (Laravel then falls back to its emergency logger) instead of being passed to Monolog unchecked.
- Requires `guzzlehttp/guzzle` ^7.5 for Laravel's HTTP client (Laravel 10 only suggests it; Laravel 11+ already requires it).
- The config file is `config/laralog-client.php` and is published with the `laralog-client` or `laralog-client-config` tag.

### Upgrading
- Require PHP 8.1+ and Laravel 10+ (`composer require laranex/laralog-client:^4.0`). Laravel 9 is not supported: Testbench 7 cannot run Pest 2+ and Composer blocks every Laravel 9 release for security advisories.
- If you referenced the handler class directly, replace `Laranex\LaralogClient\LaralogClient` with `Laranex\LaralogClient\LaralogHandler`; `driver => 'laralog'` channels need no change.
- `LaralogClientHttpException::getCode()` is now the HTTP status (or `0` for connection failures) instead of always `500`; the default constructor message is gone in favour of `unexpectedStatus()`, `connectionFailed()` and `missingConfiguration()`.
- `LARALOG_CLIENT_BASE_URL` and `LARALOG_CLIENT_TEAM_SECRET_KEY` are now required: resolving the channel without them throws instead of posting to `/api/logs` on an empty host.
- Re-publish the config if you want the new `timeout` key: `php artisan vendor:publish --tag="laralog-client-config" --force`.

## 1.0.0 - 2023-02-25

- Laralog Custom Laravel Log Driver initial release
