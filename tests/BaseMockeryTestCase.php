<?php

namespace Cogep\PhpUtils\Tests;

use Mockery\Adapter\Phpunit\MockeryTestCase;

abstract class BaseMockeryTestCase extends MockeryTestCase
{
    protected function tearDown(): void
    {
        foreach (get_object_vars($this) as $prop => $value) {
            $this->{$prop} = null;
        }
        parent::tearDown();
    }
}
