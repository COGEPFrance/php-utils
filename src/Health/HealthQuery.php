<?php

namespace Cogep\PhpUtils\Health;

use Cogep\PhpUtils\Classes\DTOInterface;
use Cogep\PhpUtils\Classes\Utils\HttpActionEnum;
use Cogep\PhpUtils\Command\BusCommand;

#[BusCommand(name: 'health', exposeApi: true, method: HttpActionEnum::GET)]
class HealthQuery implements DTOInterface
{
}
