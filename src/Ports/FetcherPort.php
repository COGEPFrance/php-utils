<?php

namespace Cogep\PhpUtils\Ports;

interface FetcherPort
{
    /**
     * @return array<array<string,mixed>>
     */
    public function fetch(): array;
}
