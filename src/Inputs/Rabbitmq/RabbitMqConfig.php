<?php

namespace Cogep\PhpUtils\Inputs\Rabbitmq;

class RabbitMqConfig
{
    /**
     * @param array<string,class-string> $queue_handler_mapping
     */
    public function __construct(
        public string $dl_queue,
        public string $dl_exchange,
        public string $dl_routing_key,
        public string $exchange,
        public array $queue_handler_mapping
    ) {
    }
}
