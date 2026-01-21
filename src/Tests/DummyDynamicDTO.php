<?php

namespace Cogep\PhpUtils\Tests;

use Cogep\PhpUtils\Classes\DTOInterface;
use Cogep\PhpUtils\Classes\DynamicProperties\DynamicPropertyClass;

class DummyDynamicDTO extends DynamicPropertyClass implements DTOInterface
{
    public int $id;

    public string $name;
}
