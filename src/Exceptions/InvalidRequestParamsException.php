<?php

namespace Apiato\Core\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class InvalidRequestParamsException extends SilenceException
{
    protected $code = Response::HTTP_EXPECTATION_FAILED;

    protected $message = 'Invalid request params.';
}
