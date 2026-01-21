<?php


namespace Cogep\PhpUtils\Tests;

use Cogep\PhpUtils\Classes\DynamicProperties\DynamicPropertyClass;
use Cogep\PhpUtils\Classes\EntityInterface;

class DummyDynamicEntity extends DynamicPropertyClass implements EntityInterface
{
    public int $id;

    public string $name;
}
