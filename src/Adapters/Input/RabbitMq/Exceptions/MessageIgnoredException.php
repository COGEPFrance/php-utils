<?php

namespace Cogep\PhpUtils\Adapters\Input\RabbitMq\Exceptions;

use Exception;
use Throwable;

class MessageIgnoredException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("Message ignoré: {$message}", $code, $previous);
    }
}
