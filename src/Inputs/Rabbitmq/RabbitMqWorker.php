<?php

namespace Cogep\PhpUtils\Inputs\Rabbitmq;

use Cogep\PhpUtils\Config\Settings;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class RabbitMqWorker
{
    private string $dlq_queue;

    /**
     * @var array<string,class-string>
     */
    private array $queue_mapping;

    public function __construct(
        private readonly AMQPStreamConnection $connection,
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly Settings $settings,
    ) {
        $this->dlq_queue = $this->settings->rabbitQueueDlq;
        $this->queue_mapping = $this->settings->getQueueMapping();
    }

    public function consume(string $queue): void
    {
        $channel = $this->connection->channel();

        $this->declareQueues($channel, $queue, $this->dlq_queue);

        if (! isset($this->queue_mapping[$queue])) {
            throw new \RuntimeException("Aucun handler configuré pour la queue : {$queue}");
        }
        $handlerClass = $this->queue_mapping[$queue];
        $handler = $this->container->get($handlerClass);

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
