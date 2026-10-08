<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Laralog Server
    |--------------------------------------------------------------------------
    |
    | The base URL of your Laralog server and the secret key of the team the
    | logs belong to. Every record is posted to "{base_url}/api/logs" with
    | the secret in the "X-TEAM-SECRET-KEY" header.
    |
    */

    'base_url' => env('LARALOG_CLIENT_BASE_URL'),

    'team_secret_key' => env('LARALOG_CLIENT_TEAM_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The number of seconds to wait for the Laralog server before the request
    | fails.
    |
    */

    'timeout' => (int) env('LARALOG_CLIENT_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Ignore Exceptions
    |--------------------------------------------------------------------------
    |
    | When true (the default) a failed request never breaks the code that
    | logged: the first failure is written to PHP's error log and the record
    | is dropped. Set it to false (e.g. in tests or CI) to throw
    | LaralogClientHttpException instead. A channel in config/logging.php
    | may override it with its own "ignore_exceptions" option.
    |
    */

    'ignore_exceptions' => (bool) env('LARALOG_CLIENT_IGNORE_EXCEPTIONS', true),

];
