<?php

namespace Cogep\PhpUtils\FileStorage;

use Cogep\PhpUtils\Classes\EntityInterface;

readonly class PersisterResultEntity implements EntityInterface
{
    public function __construct(
        public string $resource,
        public int $count,
    ) {
    }
}
