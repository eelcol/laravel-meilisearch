<?php

namespace Eelcol\LaravelMeilisearch\Exceptions;

use Exception;
use Throwable;

class OrWhereTopLevelNotSupported extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct("The use of 'orWhere' on the top-level is currently not supported.", $code, $previous);
    }
}
