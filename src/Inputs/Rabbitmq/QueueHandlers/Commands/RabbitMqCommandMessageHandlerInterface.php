<?php

namespace Cogep\PhpUtils\Inputs\Rabbitmq\QueueHandlers\Commands;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('command_message_handler')]
interface RabbitMqCommandMessageHandlerInterface
{
    /**
     * @return array<string,string>
     */
    public static function getCommandsMapping(): array;
}
