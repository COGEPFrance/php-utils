<?php

namespace Cogep\PhpUtils\FileStorage\Ports;

use Cogep\PhpUtils\FileStorage\Formats\FormatterResult;

interface FileFormatterWithWarmupLimitInterface extends FileFormatterPort
{
    /**
     * @param iterable<array<string,mixed>> $data
     */
    public function arrayToRaw(iterable $data, int $warmupLimit = 100): FormatterResult;
}
