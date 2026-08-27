<?php

namespace Cogep\PhpUtils\Tests\Fixtures\FilterData;

use Cogep\PhpUtils\Classes\FilterDataDtoInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements FilterDataDtoInterface<FilterFixture, DataFixture>
 */
final class FilterDataCommandFixture implements FilterDataDtoInterface
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Valid]
        public FilterFixture $filter,
        #[Assert\NotNull]
        #[Assert\Valid]
        public DataFixture $data,
    ) {
    }

    public function getFilter(): FilterFixture
    {
        return $this->filter;
    }

    public function getData(): DataFixture
    {
        return $this->data;
    }
}
