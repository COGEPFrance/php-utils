<?php

namespace Cogep\PhpUtils\FileStorage\Formats;

/**
 * @property-read int $count
 */
class FormatterResult
{
    public readonly \Generator $raw;

    private readonly CounterRef $counterRef;

    /**
     * @param CounterRef|int $count Pass a CounterRef for lazy (streaming) count tracking,
     *                               or an int for an already-known count.
     */
    public function __construct(\Generator $raw, CounterRef|int $count)
    {
        $this->raw = $raw;

        if (is_int($count)) {
            $ref = new CounterRef();
            $ref->value = $count;
            $this->counterRef = $ref;
        } else {
            $this->counterRef = $count;
        }
    }

    /**
     * Allows accessing ->count as a virtual property for backward compatibility.
     */
    public function __get(string $name): mixed
    {
        if ($name === 'count') {
            return $this->counterRef->value;
        }

        throw new \InvalidArgumentException(sprintf('Unknown property: %s::%s', self::class, $name));
    }

    /**
     * Returns the current count. When using a CounterRef, this will reflect
     * the actual count only after the generator has been fully consumed.
     */
    public function getCount(): int
    {
        return $this->counterRef->value;
    }
}
