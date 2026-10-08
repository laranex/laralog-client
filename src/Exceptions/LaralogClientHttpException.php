<?php

declare(strict_types=1);

namespace Laranex\LaralogClient\Exceptions;

use RuntimeException;
use Throwable;

class LaralogClientHttpException extends RuntimeException
{
    public static function unexpectedStatus(int $status, string $body): self
    {
        return new self(sprintf('Request to the Laralog server failed with status %d: %s', $status, $body), $status);
    }

    public static function connectionFailed(Throwable $previous): self
    {
        return new self('Could not connect to the Laralog server: '.$previous->getMessage(), 0, $previous);
    }

    public static function missingConfiguration(string $key): self
    {
        return new self(sprintf('The "laralog-client.%s" config value is not set.', $key));
    }
}
