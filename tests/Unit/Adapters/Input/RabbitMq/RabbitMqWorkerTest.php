<?php

namespace Unit\Adapters\Input\RabbitMq;

use Cogep\PhpUtils\Adapters\Input\RabbitMq\RabbitMqWorker;
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

    private $locator;

    private $channel;

    private RabbitMqWorker $worker;

    protected function setUp(): void
    {
        $this->connection = Mockery::mock(AMQPStreamConnection::class);
        $this->logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $this->locator = Mockery::mock(ContainerInterface::class);
        $this->channel = Mockery::mock(AMQPChannel::class);

        $this->worker = new RabbitMqWorker($this->connection, $this->logger, $this->locator, 'dlq_queue');
    }

    public function testConsumeThrowsExceptionIfHandlerMissing()
    {
        $this->connection->shouldReceive('channel')
            ->andReturn($this->channel);
        $this->channel->shouldReceive('queue_declare');

        $this->locator->shouldReceive('has')
            ->with('test_queue')
            ->andReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Aucun handler trouvé');

        $this->worker->consume('test_queue');
        $this->assertTrue(true);
    }

    public function testConsumeHandlesMessageAndAcks()
    {
        $this->connection->shouldReceive('channel')
            ->andReturn($this->channel);

        $this->channel->shouldReceive('queue_declare')
            ->twice();

        $handler = Mockery::mock(\stdClass::class);
        $handler->shouldReceive('handle')
            ->once();
        $this->locator->shouldReceive('has')
            ->andReturn(true);
        $this->locator->shouldReceive('get')
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
                'test_queue',
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

    public function testConsumeHandlesErrorAndNacks()
    {
        $this->connection->shouldReceive('channel')
            ->andReturn($this->channel);
        $this->channel->shouldReceive('queue_declare');

        $handler = Mockery::mock(\stdClass::class);
        $handler->shouldReceive('handle')
            ->andThrow(new \Exception('Erreur fatale'));

        $this->locator->shouldReceive('has')
            ->andReturn(true);
        $this->locator->shouldReceive('get')
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
