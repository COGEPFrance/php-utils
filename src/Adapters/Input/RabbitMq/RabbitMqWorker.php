<?php

namespace Cogep\PhpUtils\Adapters\Input\RabbitMq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class RabbitMqWorker
{
    public function __construct(
        private readonly AMQPStreamConnection $connection,
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $handlerLocator,
        private readonly string $dlq_queue,
    ) {
    }

    public function consume(string $queue): void
    {
        $channel = $this->connection->channel();

        $this->declareQueues($channel, $queue, $this->dlq_queue);

        if (! $this->handlerLocator->has($queue)) {
            throw new \RuntimeException("Aucun handler trouvé pour la queue : {$queue}");
        }
        $handler = $this->handlerLocator->get($queue);

        $callback = function (AMQPMessage $msg) use ($handler) {
            try {
                $body = json_decode($msg->getBody(), true);

                if (! is_array($body)) {
                    throw new \Exception('Corps du message invalide');
                }

                $this->logger->info('Message reçu', ['message', $msg]);
                $handler->handle($body, $msg);
                $msg->ack();

            } catch (\Exception $e) {
                $this->logger->error('Erreur de traitement: ' . $e->getMessage());
                $this->logger->info('Envoi du message vers la DLQ: ', [
                    'queue' => $this->dlq_queue,
                ]);

                $msg->nack();
            }
        };

        $channel->basic_consume($queue, '', false, false, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    private function declareQueues(AMQPChannel $channel, string $queue, string $dlq_queue): void
    {
        $channel->queue_declare($dlq_queue, false, true, false, false);
        $channel->queue_declare(
            $queue,
            false,
            true,
            false,
            false,
            false,
            new AMQPTable([
                'x-dead-letter-exchange' => '',
                'x-dead-letter-routing-key' => $dlq_queue,
            ])
        );
    }
}
