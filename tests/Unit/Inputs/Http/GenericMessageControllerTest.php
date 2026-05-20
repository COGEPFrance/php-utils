<?php

namespace Cogep\PhpUtils\Tests\Unit\Inputs\Http;

use Cogep\PhpUtils\Helpers\EntityValidator;
use Cogep\PhpUtils\Inputs\Http\GenericMessageController;
use Cogep\PhpUtils\Tests\BaseMockeryTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Serializer\SerializerInterface;

class GenericMessageControllerTest extends BaseMockeryTestCase
{
    private $serializer;

    private $bus;

    private $validator;

    private $controller;

    protected function setUp(): void
    {
        $this->serializer = \Mockery::mock(SerializerInterface::class);
        $this->bus = \Mockery::mock(MessageBusInterface::class);
        $this->validator = \Mockery::mock(EntityValidator::class);

        $this->controller = new GenericMessageController($this->serializer, $this->bus, $this->validator);
    }

    public function testInvokeDispatchesMessageAndReturnsResult(): void
    {
        $jsonContent = '{"name": "test"}';
        $messageClass = 'App\Message\MyMessage';
        $mockMessage = new \stdClass();
        $expectedResult = [
            'status' => 'ok',
        ];

        $request = new Request([], [], [
            '_message_class' => $messageClass,
        ], [], [], [], $jsonContent);

        $this->serializer
            ->shouldReceive('deserialize')
            ->once()
            ->with($jsonContent, $messageClass, 'json')
            ->andReturn($mockMessage);

        $this->validator
            ->shouldReceive('validate')
            ->once()
            ->with($mockMessage);

        $envelope = new Envelope($mockMessage, [new HandledStamp($expectedResult, 'handler_name')]);

        $this->bus
            ->shouldReceive('dispatch')
            ->once()
            ->with($mockMessage)
            ->andReturn($envelope);

        $response = ($this->controller)($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode($expectedResult), $response->getContent());
    }
}
