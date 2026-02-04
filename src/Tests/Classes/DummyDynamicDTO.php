<?php

namespace Cogep\PhpUtils\Tests\Classes;

use Cogep\PhpUtils\Classes\Dtos\DTOInterface;
use Cogep\PhpUtils\Classes\DynamicProperties\DynamicPropertyClass;

class DummyDynamicDTO extends DynamicPropertyClass implements DTOInterface
{
    public int $id;

    public string $name;
}
