<?php

namespace Cogep\PhpUtils\Tests\Fixtures\PHPStan;

use Cogep\PhpUtils\Classes\FilterDataDtoInterface;

/**
 * Fixture intentionnellement incorrect — utilisé par FilterDataDtoRuleTest.
 * - $filter typé array  (interdit)
 * - $data typé object   (interdit)
 * - aucun #[Assert\Valid] ni #[Assert\NotNull]
 *
 * @implements FilterDataDtoInterface<object, object>
 */
final class BadFilterDataDto implements FilterDataDtoInterface
{
    /**
     * @param array<string, mixed> $filter
     */
    public function __construct(
        public array $filter,
        public object $data,
    ) {
    }

    public function getFilter(): object
    {
        return (object) $this->filter;
    }

    public function getData(): object
    {
        return $this->data;
    }
}
