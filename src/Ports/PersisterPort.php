<?php

namespace Cogep\PhpUtils\Ports;

interface PersisterPort
{
    /**
     * @param array<array<string,mixed>> $data
     */
    public function save(array $data): void;
}
