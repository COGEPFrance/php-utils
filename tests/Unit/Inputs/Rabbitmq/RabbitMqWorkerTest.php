<?php

namespace Cogep\PhpUtils\Tests\Unit\Inputs\Rabbitmq;

use Cogep\PhpUtils\Inputs\Rabbitmq\RabbitMqWorker;
use Cogep\PhpUtils\Tests\Fixtures\TestConfig;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RabbitMqWorkerTest extends TestCase
{
    private $connection;

    private $logger;

    private $container;

    private $channel;

    private RabbitMqWorker $worker;

    private TestConfig $config;

    protected function setUp(): void
    {
        $this->connection = Mockery::mock(AMQPStreamConnection::class);
        $this->logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $this->container = Mockery::mock(ContainerInterface::class);
        $this->channel = Mockery::mock(AMQPChannel::class);
        $this->config = new TestConfig();

        $this->worker = new RabbitMqWorker($this->connection, $this->logger, $this->container, $this->config);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testConsumeThrowsExceptionIfHandlerMissing()
    {
        $this->connection->shouldReceive('channel')
            ->andReturn($this->channel);
        $this->channel->shouldReceive('queue_declare');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Aucun handler configuré pour la queue : queue_fantome');

        $this->worker->consume('queue_fantome');
    }

    public function testConsumeHandlesMessageAndAcks()
    {
        $mapping = $this->config->getQueueMapping();
        $queueName = array_key_first($mapping);
        $handlerClass = $mapping[$queueName];

        $this->connection->shouldReceive('channel')
            ->andReturn($this->channel);
        $this->channel->shouldReceive('queue_declare')
            ->twice();

        $handler = Mockery::mock(\stdClass::class);
        $handler->shouldReceive('handle')
            ->once();

        $this->container->shouldReceive('get')
            ->with($handlerClass)
            ->once()
            ->andReturn($handler);

        $message = Mockery::mock(AMQPMessage::class);
        $message->shouldReceive('getBody')
            ->andReturn(json_encode([
                'foo' => 'bar',
            ]));
        $message->shouldReceive('ack')
            ->once();

        $this->channel->shouldReceive('basic_consume')
            ->once()
            ->with(
                $queueName,
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($callback) use ($message) {
                    $callback($message);
                    return true;
                })
            );

        $this->channel->shouldReceive('is_consuming')
            ->andReturn(true, false);
        $this->channel->shouldReceive('wait')
            ->once();

        $this->worker->consume($queueName);
        $this->assertTrue(true);
    }

    public function testConsumeHandlesErrorAndNacks()
    {
        $this->connection->shouldReceive('channel')
            ->andReturn($this->channel);
        $this->channel->shouldReceive('queue_declare');

        $handler = Mockery::mock(\stdClass::class);
        $handler->shouldReceive('handle')
            ->andThrow(new \Exception('Erreur fatale'));

        $this->container->shouldReceive('has')
            ->andReturn(true);
        $this->container->shouldReceive('get')
            ->andReturn($handler);

        $message = Mockery::mock(AMQPMessage::class);
        $message->shouldReceive('getBody')
            ->andReturn(json_encode([
                'data' => 'bad',
            ]));

        $message->shouldReceive('nack')
            ->once();

        $this->channel->shouldReceive('basic_consume')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($callback) use ($message) {
                    $callback($message);
                    return true;
                })
            );

        $this->channel->shouldReceive('is_consuming')
            ->andReturn(true, false);
        $this->channel->shouldReceive('wait')
            ->once();

        $this->worker->consume('test_queue');
        $this->assertTrue(true);
    }
}
