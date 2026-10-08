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
    | fails. A failed request throws LaralogClientHttpException.
    |
    */

    'timeout' => (int) env('LARALOG_CLIENT_TIMEOUT', 5),

];
