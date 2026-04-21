<?php

namespace Cogep\PhpUtils\Exceptions;

use Throwable;

class MethodHasNoParamException extends \Exception
{
    public function __construct(string $methodName, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("La méthode {$methodName} n'a pas de paramètres.", $code, $previous);
    }
}
