<?php

namespace Cogep\PhpUtils\Adapters\Input\RabbitMq\QueueHandlers\Commands;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('command_message_handler')]
interface RabbitMqCommandMessageHandlerInterface
{
    /**
     * @return array<string,string>
     */
    public static function getCommandsMapping(): array;
}
