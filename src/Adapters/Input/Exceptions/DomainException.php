<?php

namespace Cogep\PhpUtils\Adapters\Input\Exceptions;

use Cogep\PhpUtils\Adapters\Input\ErrorCodeEnum;
use Throwable;

class DomainException extends \Exception
{
    public function __construct(
        private readonly ErrorCodeEnum $errorCode,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message ?: $this->errorCode->value, $code, $previous);
    }

    public function getErrorCode(): ErrorCodeEnum
    {
        return $this->errorCode;
    }
}
