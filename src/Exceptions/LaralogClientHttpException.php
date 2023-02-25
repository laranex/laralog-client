<?php

namespace Laranex\LaralogClient\Exceptions;

use Exception;
use Throwable;

class LaralogClientHttpException extends Exception
{
    public function __construct(string $message = 'Something went wrong in LaralogClient', int $code = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
