<?php

namespace Cogep\PhpUtils\Command;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class BusCommand
{
    public function __construct(
        public string $name,
        public bool $exposeApi = true,
        public string $method = 'POST'
    ) {
    }
}
