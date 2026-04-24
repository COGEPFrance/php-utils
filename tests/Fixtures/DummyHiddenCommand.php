<?php

namespace Cogep\PhpUtils\Tests\Fixtures;

use Cogep\PhpUtils\Command\BusCommand;

#[BusCommand(name: 'secret-command', exposeApi: false)]
class DummyHiddenCommand
{
}
