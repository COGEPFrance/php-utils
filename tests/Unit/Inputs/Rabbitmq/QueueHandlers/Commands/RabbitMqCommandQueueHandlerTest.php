<?php

namespace Cogep\PhpUtils\Tests\Unit\Inputs\Rabbitmq\QueueHandlers\Commands;

use Cogep\PhpUtils\Classes\DTOInterface;
use Cogep\PhpUtils\Classes\Responses\StandardResponseDto;
use Cogep\PhpUtils\Command\CommandRegistry;
use Cogep\PhpUtils\Enums\ErrorCodeEnum;
use Cogep\PhpUtils\Exceptions\DomainException;
use Cogep\PhpUtils\Helpers\EntityValidator;
use Cogep\PhpUtils\Inputs\Rabbitmq\Exceptions\MessageIgnoredException;
use Cogep\PhpUtils\Inputs\Rabbitmq\QueueHandlers\Commands\RabbitMqCommandQueueHandler;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class RabbitMqCommandQueueHandlerTest extends MockeryTestCase
{
    private $bus;

    private $denormalizer;

    private $serializer;

    private $logger;

    private $registry;

    private $validator;

    private RabbitMqCommandQueueHandler $masterHandler;

    protected function setUp(): void
    {
        $this->bus = Mockery::mock(MessageBusInterface::class);
        $this->denormalizer = Mockery::mock(DenormalizerInterface::class);
        $this->serializer = Mockery::mock(SerializerInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $this->registry = Mockery::mock(CommandRegistry::class);
        $this->validator = Mockery::mock(EntityValidator::class);

        $this->masterHandler = new RabbitMqCommandQueueHandler(
            $this->bus,
            $this->denormalizer,
            $this->serializer,
            $this->logger,
            $this->registry,
            $this->validator
        );
    }

    public function testHandleSuccessWithReply(): void
    {
        $data = [
            'command' => 'test:cmd',
            'data' => [
                'foo' => 'bar',
            ],
        ];
        $msg = $this->createAMQPMessageMock(true);
        /** @var Mockery\MockInterface $channel */
        $channel = $msg->getChannel();

        $this->registry->shouldReceive('getDtoClass')
            ->with('test:cmd')
            ->andReturn('MyDto');
        $dto = Mockery::mock(DTOInterface::class);
        $this->denormalizer->shouldReceive('denormalize')
            ->andReturn($dto);
        $this->validator->shouldReceive('validate')
            ->with($dto)
            ->once();

        $responseDto = StandardResponseDto::success('ok');
        $envelope = new Envelope($dto, [new HandledStamp($responseDto, 'handler')]);
        $this->bus->shouldReceive('dispatch')
            ->andReturn($envelope);

        $this->serializer->shouldReceive('serialize')
            ->once()
            ->andReturn('{"status":"success"}');
        $channel->shouldReceive('basic_publish')
            ->once();

        $this->masterHandler->handle($data, $msg);
    }

    public function testHandleDomainExceptionSendsErrorWithoutThrow(): void
    {
        $data = [
            'command' => 'test:cmd',
            'data' => [],
        ];
        $msg = $this->createAMQPMessageMock(true);
        $channel = $msg->getChannel();

        $this->registry->shouldReceive('getDtoClass')
            ->andReturn('MyDto');
        $this->denormalizer->shouldReceive('denormalize')
            ->andThrow(new DomainException(ErrorCodeEnum::NOT_FOUND, 'Pas trouvé'));

        $this->serializer->shouldReceive('serialize')
            ->once()
            ->andReturn('{"error":"NOT_FOUND"}');
        $channel->shouldReceive('basic_publish')
            ->once();

        $this->masterHandler->handle($data, $msg);
    }

    public function testHandleTechnicalFailureSendsResponseAndThrowsForDlq(): void
    {
        $data = [
            'command' => 'test:cmd',
            'data' => [],
        ];
        $msg = $this->createAMQPMessageMock(true);
        /** @var Mockery\MockInterface $channel */
        $channel = $msg->getChannel();

        $this->registry->shouldReceive('getDtoClass')
            ->andThrow(new \Exception('Crash'));

        $this->serializer->shouldReceive('serialize')
            ->once()
            ->andReturn('{"error":"INTERNAL_ERROR"}');
        $channel->shouldReceive('basic_publish')
            ->once();

        $this->expectException(\Exception::class);
        $this->masterHandler->handle($data, $msg);
    }

    public function testHandleMessageIgnoredWithoutReplyToThrowsDirectly(): void
    {
        $msg = $this->createAMQPMessageMock(false); // Pas de reply_to

        $this->expectException(MessageIgnoredException::class);
        $this->masterHandler->handle([
            'data' => [],
        ], $msg);
    }

    private function createAMQPMessageMock(bool $hasReplyTo): AMQPMessage
    {
        $msg = Mockery::mock(AMQPMessage::class);
        $channel = Mockery::mock(AMQPChannel::class);
        $channel->shouldIgnoreMissing();

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
