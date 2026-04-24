<?php

namespace Cogep\PhpUtils\Inputs\Rabbitmq\QueueHandlers;

use PhpAmqpLib\Message\AMQPMessage;

interface RabbitMqQueueHandlerInterface
{
    /**
     * @param array<string,mixed> $data
     */
    public function handle(array $data, AMQPMessage $originalMessage): void;
}
