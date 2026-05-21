<?php

namespace Cogep\PhpUtils\FileStorage\Formats;

readonly class FormatterResult
{
    /**
     * @param \Generator<string> $raw
     */
    public function __construct(
        public \Generator $raw,
        public int $count
    ) {
    }
}
