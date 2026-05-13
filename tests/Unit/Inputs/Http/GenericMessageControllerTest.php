<?php

namespace Cogep\PhpUtils\Tests\Unit\Inputs\Http;

use Cogep\PhpUtils\Helpers\EntityValidator;
use Cogep\PhpUtils\Inputs\Http\GenericMessageController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Serializer\SerializerInterface;

class GenericMessageControllerTest extends TestCase
{
    private $serializer;

    private $bus;

    private $validator;

    private $controller;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->validator = $this->createMock(EntityValidator::class);

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

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($jsonContent, $messageClass, 'json')
            ->willReturn($mockMessage);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($mockMessage);

        $envelope = new Envelope($mockMessage, [new HandledStamp($expectedResult, 'handler_name')]);

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with($mockMessage)
            ->willReturn($envelope);

        $response = ($this->controller)($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode($expectedResult), $response->getContent());
    }
}
