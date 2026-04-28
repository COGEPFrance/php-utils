<?php

namespace Cogep\PhpUtils\Tests\Fixtures;

use Cogep\PhpUtils\Command\BusCommand;

#[BusCommand(name: 'test-command', exposeApi: true)]
class DummyExposedCommand
{
}
