<?php


namespace Cogep\PhpUtils\InMemory;

use Cogep\PhpUtils\Classes\EntityInterface;

readonly class PersisterResultEntity implements EntityInterface
{
    public function __construct(
        public string $resource,
        public int    $count,
    )
    {
    }
}
