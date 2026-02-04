<?php

namespace Cogep\PhpUtils\Exceptions;

use Exception;
use Throwable;

class ObjectMustBeClassException extends Exception
{
    public function __construct(
        string $className,
        string $classNameToMatch,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct("La classe {$className} doit être un/une {$classNameToMatch}.", $code, $previous);
    }
}
