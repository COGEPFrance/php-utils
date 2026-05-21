<?php

namespace Cogep\PhpUtils\FileStorage\Ports;

interface FileFormatterWithWarmupLimitInterface extends FileFormatterPort
{
    /**
     * @param iterable<array<string,mixed>> $data
     * @return \Generator<string>
     */
    public function arrayToRaw(iterable $data, int $warmupLimit = 100): \Generator;
}
