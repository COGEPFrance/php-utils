<?php

namespace Cogep\PhpUtils\FileStorage\Formats;

/**
 * Mutable counter holder shared between a lazy generator and FormatterResult.
 * Allows the count to be updated as the generator is consumed.
 */
class CounterRef
{
    public function __construct(
        public int $value = 0
    ) {
    }
}
