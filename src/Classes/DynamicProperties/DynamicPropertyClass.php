<?php

namespace Cogep\PhpUtils\Classes\DynamicProperties;

#[\AllowDynamicProperties]
abstract class DynamicPropertyClass
{
    public function __set(string $name, mixed $value): void
    {
        $this->{$name} = $value;
    }
}
