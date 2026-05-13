<?php

namespace Cogep\PhpUtils\Command;

use Attribute;
use Cogep\PhpUtils\Classes\Utils\HttpActionEnum;

#[Attribute(Attribute::TARGET_CLASS)]
class BusCommand
{
    public function __construct(
        public string $name,
        public bool $exposeApi = true,
        public HttpActionEnum $method = HttpActionEnum::POST,
    ) {
    }
}
