<?php

namespace Inputs\Rabbitmq\QueueHandlers\Commands;

use Cogep\PhpUtils\Classes\DtoHelper;
use Cogep\PhpUtils\Classes\DTOInterface;
use Cogep\PhpUtils\Enums\ErrorCodeEnum;
use Cogep\PhpUtils\Exceptions\DomainException;
use Cogep\PhpUtils\Inputs\Rabbitmq\Exceptions\MessageIgnoredException;
use Cogep\PhpUtils\Inputs\Rabbitmq\QueueHandlers\Commands\RabbitMqCommandMessageHandlerInterface;
use Cogep\PhpUtils\Inputs\Rabbitmq\QueueHandlers\Commands\RabbitMqCommandQueueHandler;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class RabbitMqCommandQueueHandlerTest extends MockeryTestCase
{
    private RabbitMqCommandQueueHandler $masterHandler;

    private $handlerMock;

    private $dtoHelper;

    private $serializer;

    protected function setUp(): void
    {
        $this->dtoHelper = Mockery::mock(DtoHelper::class);
        $this->serializer = Mockery::mock(SerializerInterface::class);

        $this->handlerMock = Mockery::mock(RabbitMqCommandMessageHandlerInterface::class);
        $this->handlerMock->shouldReceive('getCommandsMapping')
            ->andReturn([
                'test:cmd' => 'handleTest',
            ]);

        $this->masterHandler = new RabbitMqCommandQueueHandler(
            [$this->handlerMock],
            $this->serializer,
            Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing(),
            $this->dtoHelper
        );
    }

    public function testHandleSuccessWithReply()
    {
        $data = [
            'command' => 'test:cmd',
            'data' => [
                'foo' => 'bar',
            ],
        ];
        $msg = Mockery::mock(AMQPMessage::class);
        $channel = Mockery::mock(AMQPChannel::class);

        $msg->shouldReceive('has')
            ->with('reply_to')
            ->andReturn(true);
        $msg->shouldReceive('get')
            ->with('reply_to')
            ->andReturn('reply_queue');
        $msg->shouldReceive('get')
            ->with('correlation_id')
            ->andReturn('123');
        $msg->shouldReceive('getChannel')
            ->andReturn($channel);

        $dto = Mockery::mock(DTOInterface::class);
        $this->dtoHelper->shouldReceive('fillDtoFromMethodWithGivenData')
            ->andReturn($dto);

        $this->handlerMock->shouldReceive('handleTest')
            ->with($dto)
            ->andReturn('ok');

        $this->serializer->shouldReceive('serialize')
            ->once()
            ->andReturn('{"status":"success"}');

        $channel->shouldReceive('basic_publish')
            ->once();

        $this->masterHandler->handle($data, $msg);
    }

    public function testHandleDomainExceptionSendsErrorWithoutThrow()
    {
        $data = [
            'command' => 'test:cmd',
            'data' => [],
        ];
        $msg = $this->createAMQPMessageMock(true);
        /** @var MockInterface|AMQPChannel $channel */
        $channel = $msg->getChannel();

        $this->dtoHelper->shouldReceive('fillDtoFromMethodWithGivenData')
            ->andThrow(new DomainException(ErrorCodeEnum::NOT_FOUND, 'Pas trouvé'));

        $this->serializer->shouldReceive('serialize')
            ->once()
            ->andReturn('{"error":{"code":"NOT_FOUND"}}');
        $channel->shouldReceive('basic_publish')
            ->once();

        $this->masterHandler->handle($data, $msg);
    }

    public function testHandleTechnicalFailureSendsResponseAndThrowsForDlq()
    {
        $data = [
            'command' => 'test:cmd',
            'data' => [],
        ];
        $msg = $this->createAMQPMessageMock(true);
        /** @var MockInterface|AMQPChannel $channel */
        $channel = $msg->getChannel();

        $technicalException = new \Exception('Crash serveur');
        $this->dtoHelper->shouldReceive('fillDtoFromMethodWithGivenData')
            ->andThrow($technicalException);

        $this->serializer->shouldReceive('serialize')
            ->once()
            ->andReturn('{"error":{"code":"INTERNAL_ERROR"}}');
        $channel->shouldReceive('basic_publish')
            ->once();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Crash serveur');

        $this->masterHandler->handle($data, $msg);
    }

    public function testHandleMessageIgnoredWithoutReplyToThrowsDirectly()
    {
        $msg = $this->createAMQPMessageMock(false); // Pas de reply_to

        $this->expectException(MessageIgnoredException::class);

        $this->masterHandler->handle([
            'data' => [],
        ], $msg);
    }

    /**
     * Helper pour éviter la répétition des mocks AMQP
     */
    private function createAMQPMessageMock(bool $hasReplyTo): AMQPMessage
    {
        $msg = Mockery::mock(AMQPMessage::class);
        $channel = Mockery::mock(AMQPChannel::class);

        $msg->shouldReceive('has')
            ->with('reply_to')
            ->andReturn($hasReplyTo);
        $msg->shouldReceive('getChannel')
            ->andReturn($channel);

        if ($hasReplyTo) {
            $msg->shouldReceive('get')
                ->with('reply_to')
                ->andReturn('reply_queue');
            $msg->shouldReceive('get')
                ->with('correlation_id')
                ->andReturn('123');
        }

        return $msg;
    }
}
