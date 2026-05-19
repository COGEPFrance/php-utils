<?php

namespace Cogep\PhpUtils\FileStorage\Ports;

interface FileFormatterWithWarmupLimitInterface extends FileFormatterPort
{
    public function arrayToRaw(iterable $data, int $warmupLimit = 100): \Generator;
}
